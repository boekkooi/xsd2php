<?php
namespace Goetas\Xsd\XsdToPhp\Wsdl;

use DOMDocument;
use DOMElement;
use Goetas\XML\XSDReader as XSD;
use Goetas\XML\XSDReader\Utils\UrlUtils;
use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element\Binding;
use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element\BindingOperation;
use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element\BindingOperationFault;
use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element\BindingOperationMessage;
use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element\Message;
use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element\MessagePart;
use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element\Operation;
use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element\OperationFault;
use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element\OperationParam;
use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element\PortType;
use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Schema;
use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Type\QName;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
class SchemaReader
{
    const WSDL_NS = 'http://schemas.xmlsoap.org/wsdl/';

    const XSD_NS = "http://www.w3.org/2001/XMLSchema";

    private $loadedFiles = array();

    private $knowLocationSchemas = array();

    /**
     * @var XsdSchemaReader
     */
    private $xsdSchemaReader;

    public function __construct(XsdSchemaReader $xsdSchemaReader)
    {
        $this->xsdSchemaReader = $xsdSchemaReader;
    }

    public function addKnownSchemaLocation($remote, $local)
    {
        $this->knowLocationSchemas[$remote] = $local;
    }

    /**
     *
     * @param Schema $schema
     * @param DOMElement $node
     * @param Schema $parent
     * @return array
     */
    private function schemaNode(Schema $schema, DOMElement $node, Schema $parent = null)
    {
        if ($node->hasAttribute("name")) {
            $schema->setName($node->getAttribute("name"));
        }
        if ($node->hasAttribute("targetNamespace")) {
            $schema->setTargetNamespace($node->getAttribute("targetNamespace"));
        } elseif ($parent) {
            $schema->setTargetNamespace($parent->getTargetNamespace());
        }

        foreach ($node->childNodes as $childNode) {
            switch ($childNode->localName) {
                case 'import':
                    $this->loadImport($schema, $childNode);
                    break;
                case 'types':
                    $this->loadTypes($schema, $childNode);
                    break;
                case 'message':
                    $this->loadMessage($schema, $childNode);
                    break;
                case 'portType':
                    $this->loadPortType($schema, $childNode);
                    break;
                case 'binding':
                    $this->loadBinding($schema, $childNode);
                    break;
                case 'service':
                    break;
            }
        }
    }

    /**
     * @param Schema $schema
     * @param DOMElement $node
     */
    private function loadImport(Schema $schema, DOMElement $node)
    {
        $base = urldecode($node->ownerDocument->documentURI);
        $file = UrlUtils::resolveRelativeUrl($base, $node->getAttribute("location"));

        if (isset($this->loadedFiles[$file])) {
            $schema->addSchema($this->loadedFiles[$file]);
            return;
        }

        $this->loadedFiles[$file] = $newSchema = new Schema();
        $newSchema->setLocation($file);

        $xml = $this->getDOM(
            isset($this->knowLocationSchemas[$file]) ? $this->knowLocationSchemas[$file]:$file
        );

        $this->schemaNode($newSchema, $xml->documentElement, $schema);

        if ($node->getAttribute("namespace")) {
            $schema->addSchema($newSchema);
        }
    }

    /**
     * Load the defined types nodes.
     *
     * @param Schema $schema
     * @param DOMElement $node
     * @return DOMElement[]
     */
    private function loadTypes(Schema $schema, DOMElement $node)
    {
        $xmlSchemaElements = [];
        $xmlSchemaDependencies = [];

        $used = [];
        $readyForConvert = [];
        foreach ($node->childNodes as $xsdSchemaNode) {
            if (!$xsdSchemaNode instanceof \DOMElement) {
                continue;
            }

            $typeNamespace = $xsdSchemaNode->getAttribute('targetNamespace');
            $xmlSchemaElements[$typeNamespace] = $xsdSchemaNode;

            // Resolve any internal dependencies
            $typeDependencies = [];
            $xsdSchemaImports = $xsdSchemaNode->getElementsByTagNameNS(self::XSD_NS, 'import');
            foreach ($xsdSchemaImports as $xsdSchemaImport) {
                if (!$xsdSchemaImport instanceof DOMElement || $xsdSchemaImport->hasAttribute('schemaLocation')) {
                    continue;
                }

                $typeDependencies[] = $xsdSchemaImport->getAttribute('namespace');
            }
            $xmlSchemaDependencies[$typeNamespace] = $typeDependencies;

            // There are no internal dependencies so the node is ready for the xsd schema reader
            if (empty($typeDependencies)) {
                $used[] = $typeNamespace;
                $readyForConvert[] = $typeNamespace;
            }
        }

        // Resolve internal dependency order (topological Sort)
        $depending = $xmlSchemaDependencies;
        while (!empty($used)) {
            $usedId = array_shift($used);

            foreach (array_keys($depending) as $dependingId) {
                $depending[$dependingId] = array_diff(
                    $depending[$dependingId],
                    array($usedId)
                );
                if (!empty($depending[$dependingId])) {
                    continue;
                }

                $used[] = $dependingId;
                $readyForConvert[] = $dependingId;
                unset($depending[$dependingId]);
            }
        }

        if (!empty($depending)) {
            throw new \RuntimeException('Invalid xsd schema in wsdl circular reference found.');
        }

        // Read the xsd schema's
        $xsdSchemas = [];
        foreach ($readyForConvert as $namespace) {
            $xsdSchemaDependencies = [];
            foreach ($xmlSchemaDependencies[$namespace] as $dependencies) {
                $xsdSchemaDependencies[] = $xsdSchemas[$dependencies];
            }

            $xsdSchema = $this->xsdSchemaReader->readNode(
                $xmlSchemaElements[$namespace],
                'schema.xsd',
                $xsdSchemaDependencies
            );

            $xsdSchemas[$namespace] = $xsdSchema;
            $schema->addType($xsdSchema);
        }
    }

    /**
     * wsdl:message
     *
     * @param $schema
     * @param DOMElement $node
     * @return \Closure
     */
    private function loadMessage(Schema $schema, DOMElement $node)
    {
        $message = new Message($node->getAttribute('name'));
        foreach ($node->childNodes as $partNode) {
            if (!$partNode instanceof DOMElement) {
                continue;
            }

            $element = null;
            if ($partNode->hasAttribute('element')) {
                $element = QName::create($partNode->getAttribute('element'), $partNode);
            }

            $type = null;
            if ($partNode->hasAttribute('type')) {
                $type = QName::create($partNode->getAttribute('type'), $partNode);
            }

            $message->addPart(new MessagePart(
                $partNode->getAttribute('name'),
                $element,
                $type
            ));
        }

        $schema->addMessage($message);
    }

    /**
     * @param Schema $schema
     * @param DOMElement $node
     * @return \Closure
     */
    private function loadPortType(Schema $schema, DOMElement $node)
    {
        $portType = new PortType($node->getAttribute('name'));
        foreach ($node->childNodes as $operationNode) {
            if (!$operationNode instanceof DOMElement) {
                continue;
            }

            $input = $output = $fault = null;
            foreach ($operationNode->childNodes as $operationEltNode) {
                switch ($operationEltNode->localName) {
                    case 'input':
                        $input = $this->loadOperationParam($operationEltNode);
                        break;
                    case 'output':
                        $output = $this->loadOperationParam($operationEltNode);
                        break;
                    case 'fault':
                        $fault = $this->loadOperationFault($operationEltNode);
                        break;
                }
            }

            $portType->addOperation(
                new Operation(
                    $operationNode->getAttribute('name'),
                    $input,
                    $output,
                    $fault,
                    $operationNode->hasAttribute('parameterOrder') ? $operationNode->getAttribute('parameterOrder') : null
                )
            );
        }

        $schema->addPortType($portType);
    }

    private function loadOperationParam(DOMElement $node)
    {
        return new OperationParam(
            QName::create($node->getAttribute('message'), $node),
            $node->hasAttribute('name') ? $node->getAttribute('name') : null
        );
    }

    private function loadOperationFault(DOMElement $node)
    {
        return new OperationFault(
            $node->getAttribute('name'),
            QName::create($node->getAttribute('message'), $node)
        );
    }

    /**
     * @param Schema $schema
     * @param DOMElement $node
     */
    private function loadBinding(Schema $schema, DOMElement $node)
    {
        $binding = new Binding(
            $node->getAttribute('name'),
            QName::create($node->getAttribute('type'), $node)
        );
        foreach ($node->childNodes as $operationNode) {
            if (!$operationNode instanceof DOMElement) {
                continue;
            }

            $input = $output = null;
            $faults = [];
            foreach ($operationNode->childNodes as $operationEltNode) {
                switch ($operationEltNode->localName) {
                    case 'input':
                        $input = $this->loadBindingMessage($operationEltNode);
                        break;
                    case 'output':
                        $output = $this->loadBindingMessage($operationEltNode);
                        break;
                    case 'fault':
                        $faults[] = $this->loadBindingFault($operationEltNode);
                        break;
                }
            }

            $binding->addOperation(
                new BindingOperation(
                    $operationNode->getAttribute('name'),
                    $input,
                    $output,
                    $faults
                )
            );
        }

        $schema->addBinding($binding);
    }

    private function loadBindingMessage(DOMElement $node)
    {
        return new BindingOperationMessage(
            $node->hasAttribute('name') ? $node->getAttribute('name') : null
        );
    }

    private function loadBindingFault(DOMElement $node)
    {
        return new BindingOperationFault(
            $node->getAttribute('name')
        );
    }

    /**
     * @param DOMElement $node
     * @param string $file
     * @return Schema
     */
    public function readNode(DOMElement $node, $file = 'schema.wsdl')
    {
        $this->loadedFiles[$file] = $rootSchema = new Schema();
        $rootSchema->setLocation($file);

        $this->schemaNode($rootSchema, $node);

        return $rootSchema;
    }

    /**
     * @param $file
     * @return Schema
     * @throws XSD\Exception\IOException
     */
    public function readFile($file)
    {
        $xml = $this->getDOM($file);
        return $this->readNode($xml->documentElement, $file);
    }

    /**
     * @param string $file
     * @throws XSD\Exception\IOException
     * @return \DOMDocument
     */
    private function getDOM($file)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        if (! $xml->load($file)) {
            throw new XSD\Exception\IOException("Can't load the file $file");
        }
        return $xml;
    }
}
