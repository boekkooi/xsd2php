<?php
namespace Goetas\Xsd\XsdToPhp\Php;

use Goetas\Xsd\XsdToPhp\Code\Generator\ClassGenerator;
use Goetas\Xsd\XsdToPhp\Code\Generator\ElementClassGenerator;
use Goetas\Xsd\XsdToPhp\Code\Generator\Property\AttributePropertyGenerator;
use Goetas\Xsd\XsdToPhp\Code\Generator\Property\ElementPropertyGenerator;
use Goetas\Xsd\XsdToPhp\Code\Generator\SimpleTypeClassGenerator;
use Goetas\Xsd\XsdToPhp\Code\Generator\TypeClassGenerator;
use Goetas\Xsd\XsdToPhp\Converter\Configuration;
use Goetas\XML\XSDReader\Schema\Schema;
use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Type\BaseComplexType;
use Goetas\XML\XSDReader\Schema\Type\ComplexType;
use Goetas\XML\XSDReader\Schema\Element\Element;
use Goetas\XML\XSDReader\Schema\Item;
use Goetas\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use Goetas\XML\XSDReader\Schema\Element\Group;
use Goetas\XML\XSDReader\Schema\Type\SimpleType;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeItem;
use Goetas\XML\XSDReader\Schema\Element\ElementRef;
use Goetas\XML\XSDReader\Schema\Element\ElementDef;
use Goetas\Xsd\XsdToPhp\Xsd\Converter;
use Goetas\Xsd\XsdToPhp\Xsd\Helper;

class PhpConverter extends Converter
{
    /**
     * @var ClassGenerator[]
     */
    private $classes = [];

    public function __construct(Configuration $configuration)
    {
        parent::__construct($configuration);

        // XMLSchema PHP types
        $configuration->addTypeAlias("http://www.w3.org/2001/XMLSchema", "dateTime", 'DateTime');
        $configuration->addTypeAlias("http://www.w3.org/2001/XMLSchema", "time", 'DateTime');
        $configuration->addTypeAlias("http://www.w3.org/2001/XMLSchema", "date", 'DateTime');
        $configuration->addTypeAlias("http://www.w3.org/2001/XMLSchema", "anySimpleType", 'mixed');
        $configuration->addTypeAlias("http://www.w3.org/2001/XMLSchema", "anyType", 'mixed');
    }

    public function convert(array $schemas)
    {
        $visited = array();
        $this->classes = array();

        foreach ($schemas as $schema) {
            $this->navigate($schema, $visited);
        }

        return $this->getClasses();
    }

    /**
     * Retrieve all generated classes.
     *
     * @return ClassGenerator[]
     */
    private function getClasses()
    {
        $classes = [];
        foreach($this->classes as $class) {
            if ($class->skip()) {
                continue;
            }
            $classes[$class->getFQCN()] = $class->resolve();
        }
        ksort($classes);

        return $classes;
    }

    private function navigate(Schema $schema, array &$visited)
    {
        $schemaHash = spl_object_hash($schema);
        if (isset($visited[$schemaHash])) {
            return;
        }
        $visited[$schemaHash] = true;

        // Visit the types
        foreach ($schema->getTypes() as $type) {
            $this->visitType($type);
        }

        // Visit the elements
        foreach ($schema->getElements() as $element) {
            $this->visitElementDef($element);
        }

        // Visit imported schema's
        /** @var Schema $childSchema */
        foreach ($schema->getSchemas() as $childSchema) {
            if ($this->getConfiguration()->isExcludedNamespace($childSchema->getTargetNamespace())) {
                continue;
            }

            $this->navigate($childSchema, $visited);
        }
    }

    private function visitTypeBase(ClassGenerator $class, Type $type)
    {
        $class->setAbstract($type->isAbstract());

        if ($type instanceof SimpleType) {
            $this->visitSimpleType($class, $type);
        }
        if ($type instanceof BaseComplexType) {
            $this->visitBaseComplexType($class, $type);
        }
        if ($type instanceof ComplexType) {
            $this->visitComplexType($class, $type);
        }
    }

    private function visitGroup(ClassGenerator $class, Schema $schema, Group $group)
    {
        foreach ($group->getElements() as $childGroup) {
            if ($childGroup instanceof Group) {
                $this->visitGroup($class, $schema, $childGroup);
            } else {
                $property = $this->visitElement($class, $schema, $childGroup);
                $class->addPropertyFromGenerator($property);
            }
        }
    }

    private function visitAttributeGroup(ClassGenerator $class, Schema $schema, AttributeGroup $att)
    {
        foreach ($att->getAttributes() as $childAttr) {
            if ($childAttr instanceof AttributeGroup) {
                $this->visitAttributeGroup($class, $schema, $childAttr);
            } else {
                $property = $this->visitAttribute($class, $childAttr);
                $class->addPropertyFromGenerator($property);
            }
        }
    }

    private function visitElementDef(ElementDef $element)
    {
        $elementHash = spl_object_hash($element);
        if (isset($this->classes[$elementHash])) {
            return $this->classes[$elementHash];
        }

        $this->classes[$elementHash] = $class = ElementClassGenerator::create($this->getConfiguration(), $element);

        if (!$element->getType()->getName()) {
            $this->visitTypeBase($class, $element->getType());
        } else {
            $this->handleClassExtension($class, $element->getType());
        }

        return $class;
    }

    /**
     *
     * @param Type $type
     * @param boolean $force
     * @return ClassGenerator
     */
    private function visitType(Type $type, $force = false)
    {
        $cfg = $this->getConfiguration();

        $typeHash = spl_object_hash($type);

        // Don't build a type class twice
        if (isset($this->classes[$typeHash])) {
            $class = $this->classes[$typeHash];

            if (
                $force &&
                !($type instanceof SimpleType) &&
                (!$class instanceof TypeClassGenerator || !$class->hasAlias())
            ) {
                $class->removeFlag(ClassGenerator::FLAG_SKIP);
            }

            return $class;
        }

        $this->classes[$typeHash] = $class = TypeClassGenerator::create($cfg, $type, $force);

        // Skip the class if it has a alias
        if ($class->hasAlias()) {
            return $class;
        }

        $this->visitTypeBase($class, $type);

        return $class;
    }

    /**
     * @param Type $type
     * @param string $name
     * @param ClassGenerator $parentClass
     * @return ClassGenerator
     */
    private function visitTypeAnonymous(Type $type, $name, ClassGenerator $parentClass)
    {
        $typeHash = spl_object_hash($type);
        if (isset($this->classes[$typeHash])) {
            return $this->classes[$typeHash];
        }

        $this->classes[$typeHash] = $class = new ClassGenerator(
            $this->getNamingStrategy()->getAnonymousTypeName($name),
            $parentClass->getFQCN()
        );

        if ($type instanceof SimpleType) {
            $class->addFlag(ClassGenerator::FLAG_SKIP);
        }

        $class->getDocBlock()
            ->setLongDescription($type->getDoc());

        $this->visitTypeBase($class, $type);

        return $class;
    }

    private function visitComplexType(ClassGenerator $class, ComplexType $type)
    {
        $schema = $type->getSchema();
        foreach ($type->getElements() as $element) {
            if ($element instanceof Group) {
                $this->visitGroup($class, $schema, $element);
            } else {
                $property = $this->visitElement($class, $schema, $element);
                $class->addPropertyFromGenerator($property);
            }
        }
    }

    private function visitSimpleType(ClassGenerator $class, SimpleType $type)
    {
        if ($restriction = $type->getRestriction()) {
            $parent = $restriction->getBase();

            if ($parent instanceof Type) {
                $this->handleClassExtension($class, $parent);
            }

            // TODO:
            foreach ($restriction->getChecks() as $typeCheck => $checks) {
                foreach ($checks as $check) {
                    var_dump($typeCheck , $check);
//                    $class->addCheck('__value', $typeCheck, $check);
                }
            }
        }

        if ($unions = $type->getUnions()) {
            /** @var ClassGenerator[] $types */
            $types = array();
            foreach ($unions as $i => $union) {
                if (! $union->getName()) {
                    $types[] = $this->visitTypeAnonymous($union, $type->getName() . $i, $class);
                } else {
                    $types[] = $this->visitType($union);
                }
            }

            if ($candidate = reset($types)) {
                $class->setExtendsClassGenerator($candidate);
            }
        }
    }

    private function handleClassExtension(ClassGenerator $childClass, Type $type)
    {
        $cfg = $this->getConfiguration();

        // Check that the type is not a alias
        if (!$cfg->hasTypeAlias($type->getSchema()->getTargetNamespace(), $type->getName())) {
            // Fetch/Generate the extending type
            $extension = $this->visitType($type, true);

            // Mark is as the parent of the class
            $childClass->setExtendsClassGenerator($extension);
            return;
        }

        // The parent class is a alias generate it
        // TODO fix the following:
        list($name, $namespace) = $cfg->resolvePHPTypeName(
            $type->getSchema()->getTargetNamespace(),
            $type->getName()
        );

        if (isset($this->classes[spl_object_hash($type)])) {
            $class = $this->classes[spl_object_hash($type)];
        } else {
            $this->classes[spl_object_hash($type)] = $class = new SimpleTypeClassGenerator($name, $namespace);
            $class->setFlags(ClassGenerator::FLAG_SKIP);
        }
        $childClass->setExtendsClassGenerator($class);
    }

    private function visitBaseComplexType(ClassGenerator $class, BaseComplexType $type)
    {
        // Handle inheritance
        $parent = $type->getParent();
        if ($parent) {
            $parentType = $parent->getBase();
            if ($parentType instanceof Type) {
                $this->handleClassExtension($class, $parentType);
            }
        }

        $schema = $type->getSchema();
        foreach ($type->getAttributes() as $attr) {
            if ($attr instanceof AttributeGroup) {
                $this->visitAttributeGroup($class, $schema, $attr);
            } else {
                $property = $this->visitAttribute($class, $attr);
                $class->addPropertyFromGenerator($property);
            }
        }
    }

    /**
     * @param ClassGenerator $class
     * @param AttributeItem $attribute
     * @param bool|true $arrayize
     * @return AttributePropertyGenerator
     */
    private function visitAttribute(ClassGenerator $class, AttributeItem $attribute, $arrayize = true)
    {
        /** @var \Goetas\XML\XSDReader\Schema\Item|AttributeItem $attribute */
        if ($arrayize && $itemOfArray = Helper::getArrayType($attribute->getType())) {
            if ($attribute->getType()->getName()) {
                $type = $this->visitType($itemOfArray);
            } else {
                $type = $this->visitTypeAnonymous($attribute->getType(), $attribute->getName(), $class);
            }
        } else {
            $type = $this->findPHPClass($class, $attribute, true);
        }

        return new AttributePropertyGenerator($attribute, $type->getFQCN());
    }

    /**
     *
     * @param ClassGenerator $class
     * @param Schema $schema
     * @param Element $element
     * @param boolean $array
     * @return ElementPropertyGenerator
     */
    private function visitElement(ClassGenerator $class, Schema $schema, Element $element, $array = true)
    {
        $typeHint = null;
        $property = null;
        if ($array) {
            $type = $element->getType();
            if ($itemOfArray = Helper::getArrayType($type)) {
                if (!$itemOfArray->getName()) {
                    $classType = $this->visitTypeAnonymous($itemOfArray, $element->getName(), $class);
                } else {
                    $classType = $this->visitType($itemOfArray);
                }
                $typeHint = sprintf('%s[]', $classType->getFQCN());
            } elseif ($itemOfArray = Helper::getArrayNestedElement($type)) {
                if (!$type->getName()) {
                    $classType = $this->visitTypeAnonymous($type, $element->getName(), $class);
                } else {
                    $classType = $this->visitType($type);
                }

                $property = $this->visitElement($classType, $schema, $itemOfArray, false);
                $typeHint = $property->getType().'[]';
            } elseif (Helper::isArrayElement($element)) {
                $classType = $this->findPHPClass($class, $element);

                $typeHint = sprintf('%s[]', $classType->getFQCN());
            }
        }

        if ($typeHint === null) {
            /** @var \Goetas\Xsd\XsdToPhp\Code\Generator\TypeClassGenerator $type */
            $type = $this->findPHPClass($class, $element, true);
            $typeHint = $type->getFQCN();
        }

        return new ElementPropertyGenerator($element, $typeHint);
    }

    private function findPHPClass(ClassGenerator $class, Item $node, $force = false)
    {
        if ($node instanceof ElementRef) {
            return $this->visitElementDef($node->getReferencedElement());
        }

        if (!$node->getType()->getName()) {
            return $this->visitTypeAnonymous($node->getType(), $node->getName(), $class);
        }
        return $this->visitType($node->getType(), $force);
    }
}
