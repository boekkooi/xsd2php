<?php
namespace Goetas\Xsd\XsdToPhp\Wsdl;

use Goetas\XML\XSDReader\Schema\Element\ElementDef;
use Goetas\Xsd\XsdToPhp\Converter\Converter;
use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Type\QName;
use Goetas\XML\XSDReader as XSD;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\ValueGenerator;

class SoapClientConverter implements Converter
{
    /**
     * @var Converter
     */
    private $converter;

    /**
     * Temp variable used in the convertSchema method to contains a set of types.
     * This will look like [$xmlNamespace][$xmlType] = $xmlNamespace
     * @var array
     */
    private $usedTypes;

    public function __construct(Converter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * @param Schema\Schema[] $schemas
     * @return ClassGenerator[]
     */
    public function convert(array $schemas)
    {
        $classes = [];
        foreach ($schemas as $schema) {
            $classes = array_merge(
                $classes,
                $this->convertSchema($schema)
            );
        }

        return $classes;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        return $this->converter->getConfiguration();
    }

    protected function convertSchema(Schema\Schema $schema)
    {
        if (!$this->getConfiguration()->hasNamespace($schema->getTargetNamespace())) {
            throw new \InvalidArgumentException(sprintf(
                "Can't find a PHP namespace to '%s' namespace",
                $schema->getTargetNamespace()
            ));
        }

        $this->usedTypes = [];

        // Setup  client class
        $class = new ClassGenerator(
            $schema->getName() === null ? 'SoapClient' : $schema->getName() . 'Client',
            $this->getConfiguration()->getNamespace($schema->getTargetNamespace()),
            null,
            \SoapClient::class
        );
        $class->addUse(\SoapClient::class);

        $class->addMethodFromGenerator(
            $this->generateConstructor($schema)
        );

        // Add client methods
        $this->convertSchemaPortTypes($schema, $class);
        foreach ($schema->getSchemas() as $importedSchema) {
            $this->convertSchemaPortTypes($importedSchema, $class);
        }

        // Resolve used types
        $this->resolveUsedTypes($schema);

        // Generate a set of new xsd schema's with only the needed types
        $xsdSchema = $this->generateXsdSchemas($schema);

        // Let the converter convert the schema's used
        $classes = $this->converter->convert([ $xsdSchema ]);

        // Generate the class map
        $class->addPropertyFromGenerator(
            $this->generateClassMap($class, $classes)
        );
        $classes[] = $class;

        $this->usedTypes = [];

        return $classes;
    }

    /**
     * Generate the constructor method.
     *
     * @param Schema\Schema $schema
     * @return MethodGenerator
     */
    protected function generateConstructor(Schema\Schema $schema)
    {
        $method = new MethodGenerator(
            '__construct', [
            new ParameterGenerator('options', 'array', new ValueGenerator([], ValueGenerator::TYPE_ARRAY)),
            new ParameterGenerator('wsdl', 'string', $schema->getLocation())
        ],
            MethodGenerator::FLAG_PUBLIC,
            implode("\n", array(
                'if (empty($options[\'classmap\'])) {',
                '    $options[\'classmap\'] = self::$classMap;',
                '} else {',
                '    foreach (self::$classMap as $soapType => $phpType) {',
                '        if (!isset($options[\'classmap\'][$soapType])) {',
                '            $options[\'classmap\'][$soapType] = $phpType;',
                '        }',
                '    }',
                '}',
                'parent::__construct($wsdl, $options);'
            ))
        );
        return $method;
    }

    /**
     * @param Schema\Schema $schema
     * @param $class
     */
    protected function convertSchemaPortTypes(Schema\Schema $schema, ClassGenerator $class)
    {
        foreach ($schema->getPortTypes() as $portType) {
            foreach ($portType->getOperations() as $operation) {
                $class->addMethodFromGenerator(
                    $this->generateOperation($class, $operation, $schema)
                );
            }
        }
    }

    /**
     * Generate a method for a operation.
     *
     * @param ClassGenerator $class
     * @param Schema\Element\Operation $operation
     * @param Schema\Schema $schema
     * @return MethodGenerator
     */
    private function generateOperation(ClassGenerator $class, Schema\Element\Operation $operation, Schema\Schema $schema)
    {
        $method = new MethodGenerator($operation->getName());
        $methodDocBlock = new DocBlockGenerator();
        $method->setDocBlock($methodDocBlock);

        // Arguments/Parameters/Input
        if ($operation->getInput()) {
            $messageRef = $operation->getInput()->getMessage();
            $message = $this->findSchemaMessageByName($schema, $messageRef->getName());

            foreach ($message->getParts() as $part) {
                $partPhpType = $this->visitQName($class, $schema, $part->getElement());

                $method->setParameter(new ParameterGenerator(
                    $part->getName(),
                    $partPhpType
                ));

                $methodDocBlock->setTag(new Tag\ParamTag($part->getName(), $partPhpType));
            }
        }

        // Return/ouput
        if ($operation->getOutput()) {
            $messageRef = $operation->getOutput()->getMessage();
            $message = $this->findSchemaMessageByName($schema, $messageRef->getName());

            if (count($message->getParts()) > 1) {
                throw new \RuntimeException('This message %s has multiple output parts with is currently not supported.');
            }
            if (count($message->getParts()) === 1) {
                $part = current($message->getParts());
                $partPhpType = $this->visitQName($class, $schema, $part->getElement());

                $methodDocBlock->setTag(new Tag\ReturnTag($partPhpType));
            } else {
                $methodDocBlock->setTag(new Tag\ReturnTag('mixed'));
            }
        }

        // Body
        $method->setBody(sprintf(
            'return $this->__soapCall(\'%s\', array(%s));',
            $operation->getName(),
            implode(', ', array_map(
                function (ParameterGenerator $gen) {
                    return '$' . $gen->getName();
                },
                $method->getParameters())
            )
        ));

        return $method;
    }

    /**
     * Generate the class map for the soap client.
     *
     * @param ClassGenerator $class
     * @param ClassGenerator[] $usedClasses
     * @return PropertyGenerator
     */
    protected function generateClassMap(ClassGenerator $class, array $usedClasses)
    {
        $availableClasses = [];
        foreach ($usedClasses as $usedClass) {
            $availableClasses[] = '\\' . $usedClass->getNamespaceName() . '\\' . $usedClass->getName();
        }

        $classMap = [];
        foreach ($this->usedTypes as $namespace => $types) {
            $phpNamespace = $this->getConfiguration()->getNamespace($namespace);

            foreach ($types as $xmlType => $typeFQCN) {
                if (!in_array($typeFQCN, $availableClasses, true)) {
                    continue;
                }

                if (isset($classMap[$xmlType])) {
                    $classMap[$namespace.'#'.$xmlType] = new ValueGenerator(
                        $typeFQCN . '::class',
                        ValueGenerator::TYPE_CONSTANT
                    );
                    /*
                    throw new \RuntimeException(sprintf(
                        'Duplicate type found in class map (%s => %s) .',
                        $xmlType,
                        $classMap[$xmlType]
                    ));
                    */
                    continue;
                }

                if (
                    $phpNamespace === $class->getNamespaceName() ||
                    in_array(ltrim($typeFQCN, '\\'), $class->getUses(), true)
                ) {
                    $typeFQCN = substr($typeFQCN, strrpos($typeFQCN, '\\') + 1);
                }

                $classMap[$xmlType] = new ValueGenerator(
                    $typeFQCN . '::class',
                    ValueGenerator::TYPE_CONSTANT
                );
            }
        }

        return new PropertyGenerator(
            'classMap',
            $classMap,
            PropertyGenerator::FLAG_PRIVATE | PropertyGenerator::FLAG_STATIC
        );
    }

    /**
     * @param Schema\Schema $wsdlSchema
     */
    protected function resolveUsedTypes(Schema\Schema $wsdlSchema)
    {
        $typesToResolve = $this->usedTypes;
        $resolvedTypes = [];
        while (!empty($typesToResolve)) {
            $elements = reset($typesToResolve);
            $namespace = key($typesToResolve);
            array_shift($typesToResolve);

            $schemas = $this->findXsdSchemasByNamespace($wsdlSchema, $namespace);
            if (empty($schemas)) {
                throw new \RuntimeException(sprintf('Unknown namespace %s', $schemas));
            }

            while (!empty($elements)) {
                $phpClass = reset($elements);
                $name = key($elements);
                array_shift($elements);
                if (isset($resolvedTypes[$namespace][$name])) {
                    continue;
                }
                $resolvedTypes[$namespace][$name] = $phpClass;

                foreach ($schemas as $schema) {
                    // Try and find the type
                    $type = $schema->getType($name);
                    if ($type === false) {
                        // No type then find the element
                        $element = $schema->getElement($name);
                        if ($element === false || !$element instanceof XSD\Schema\Item) {
                            // TODO throw exception?
                            continue;
                        }
                        $type = $element->getType();
                    }

                    if (!$type instanceof XSD\Schema\Type\ComplexType) {
                        break;
                    }

                    /** @var XSD\Schema\Item $childElement */
                    foreach ($type->getElements() as $childElement) {
                        if (!$childElement->getType() instanceof XSD\Schema\Type\ComplexType) {
                            continue;
                        }

                        list($childXsdNamespace, $childXsdName, $childClassNamespace, $childClassName) =
                            $this->getXsdElementTypeClass($childElement);

                        $childFQCN = '\\' . $childClassNamespace . '\\' . $childClassName;

                        if ($childXsdNamespace === $namespace) {
                            $elements[$childXsdName] = $childFQCN;
                        } else {
                            $typesToResolve[$childXsdNamespace][$childXsdName] = $childFQCN;
                        }
                    }
                }
            }
        }

        $this->usedTypes = $resolvedTypes;
    }

    /**
     * Visit a QName and add it's PHP class name to the class instance and return the name.
     *
     * @param ClassGenerator $class
     * @param Schema\Schema $wsdlSchema
     * @param QName $qName
     * @return mixed
     */
    private function visitQName(ClassGenerator $class, Schema\Schema $wsdlSchema, QName $qName)
    {
        /** @var XSD\Schema\Element\ElementDef $element */
        $element = $this->findXsdSchemaElementByName($wsdlSchema, $qName->getNamespace(), $qName->getName());

        list($xsdNamespace, $xsdName, $classNamespace, $className) = $this->getXsdElementTypeClass($element);

        if ($class->getNamespaceName() !== $classNamespace) {
            $class->addUse($classNamespace . '\\' . $className);
        }
        $this->usedTypes[$xsdNamespace][$xsdName] = '\\' . $classNamespace . '\\' . $className;

        return $className;
    }

    /**
     * @param Schema\Schema $schema
     * @param string $name
     * @return Schema\Element\Message
     */
    private function findSchemaMessageByName(Schema\Schema $schema, $name)
    {
        foreach ($schema->getMessages() as $message) {
            if ($message->getName() !== $name) {
                continue;
            }
            return $message;
        }

        throw new \RuntimeException(sprintf('Unable to find wsdl message with name %s', $name));
    }

    /**
     * @param Schema\Schema $wsdlSchema
     * @param string $namespace
     * @param string $elementName
     * @return XSD\Schema\Element\ElementItem
     */
    private function findXsdSchemaElementByName(Schema\Schema $wsdlSchema, $namespace, $elementName)
    {
        foreach ($this->findXsdSchemasByNamespace($wsdlSchema, $namespace) as $schema) {
            $elt = $schema->getElement($elementName);
            if ($elt !== false) {
                return $elt;
            }
        }

        throw new \RuntimeException(sprintf('Unable to find element %s in %s', $elementName, $namespace));
    }

    /**
     * @param Schema\Schema $schema
     * @param string $namespace
     * @return XSD\Schema\Schema[]
     */
    private function findXsdSchemasByNamespace(Schema\Schema $schema, $namespace)
    {
        $schemas = [];
        foreach ($schema->getTypes() as $xsdSchema) {
            if ($xsdSchema->getTargetNamespace() === $namespace) {
                $schemas[] = $xsdSchema;
                continue;
            }

            // Search in internal/imported xsd schema's
            $visited = [ $xsdSchema->getTargetNamespace() ];
            $internalSchemas = $xsdSchema->getSchemas();
            while (!empty($internalSchemas)) {
                $internalSchema = array_pop($internalSchemas);
                if (in_array($internalSchema->getTargetNamespace(), $visited, true)) {
                    continue;
                }
                $visited[] = $internalSchema->getTargetNamespace();

                $internalSchemas = array_merge($internalSchemas, $internalSchema->getSchemas());
                if ($internalSchema->getTargetNamespace() === $namespace) {
                    $schemas[] = $internalSchema;
                }
            }
        }

        // Search imported wsdl schema's
        foreach ($schema->getSchemas() as $internalWsdlSchema) {
            $schemas = array_merge(
                $schemas,
                $this->findXsdSchemasByNamespace($internalWsdlSchema, $namespace)
            );
        }

        return $schemas;
    }

    /**
     * @param XSD\Schema\Item $element
     * @return array
     */
    private function getXsdElementTypeClass(XSD\Schema\Item $element)
    {
        $typeName = $element->getType()->getName();
        if ($typeName === null) {
            // The type is embedded in the element
            $typeName = $element->getName();
            $typeNamespace = $element->getSchema()->getTargetNamespace();

            list($className, $classNamespace) = $this->getConfiguration()->resolvePHPItemName(
                $typeNamespace,
                $typeName
            );
        } else {
            $typeName = $element->getType()->getName();
            $typeNamespace = $element->getType()->getSchema()->getTargetNamespace();

            // Go reusable types
            list($className, $classNamespace) = $this->getConfiguration()->resolvePHPTypeName(
                $typeNamespace,
                $typeName
            );
        }

        return [$typeNamespace, $typeName, $classNamespace, $className];
    }

    /**
     * Generate a new schema based on the types and elements used by the soap client.
     *
     * @param Schema\Schema $wsdlSchema
     * @return XSD\Schema\Schema
     * @throws XSD\Schema\Exception\SchemaException
     */
    private function generateXsdSchemas(Schema\Schema $wsdlSchema)
    {
        $rootSchema = new XSD\Schema\Schema();
        $rootSchema->setTargetNamespace($wsdlSchema->getTargetNamespace());

        foreach ($this->usedTypes as $namespace => $items) {
            $sourceSchemas = $this->findXsdSchemasByNamespace($wsdlSchema, $namespace);
            if (empty($sourceSchemas)) {
                throw new \RuntimeException(sprintf('Unknown namespace %s', $sourceSchemas));
            }

            if ($rootSchema->getTargetNamespace() === $namespace) {
                $newSchema = $rootSchema;
            } else {
                $newSchema = new XSD\Schema\Schema();
                $newSchema->setTargetNamespace($namespace);
                $rootSchema->addSchema($newSchema, $namespace);
            }
            foreach (array_keys($items) as $itemName) {
                foreach ($sourceSchemas as $sourceSchema) {
                    // Try and find the type
                    $typeItem = $sourceSchema->getType($itemName);
                    if ($typeItem !== false) {
                        $newSchema->addType($typeItem);
                        continue;
                    }

                    /** @var ElementDef $elementItem */
                    $elementItem = $sourceSchema->getElement($itemName);
                    if ($elementItem !== false) {
                        $newSchema->addElement($elementItem);
                        continue;
                    }
                }
            }
        }
        return $rootSchema;
    }
}
