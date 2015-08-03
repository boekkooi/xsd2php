<?php
namespace Goetas\Xsd\XsdToPhp\Wsdl\Schema;

use Goetas\XML\XSDReader as XSD;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
class Schema
{
    /**
     * @var string|null
     */
    private $location;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $targetNamespace;

    /**
     * @var XSD\Schema\Schema[]
     */
    private $types = [];
    /**
     * @var Schema[]
     */
    private $schemas = [];
    /**
     * @var Element\Message[]
     */
    private $messages = [];
    /**
     * @var Element\PortType[]
     */
    private $portTypes = [];
    /**
     * @var Element\Binding[]
     */
    private $bindings = [];

    /**
     * @param string $file
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return null|string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getTargetNamespace()
    {
        return $this->targetNamespace;
    }

    public function setTargetNamespace($targetNamespace)
    {
        $this->targetNamespace = $targetNamespace;
    }

    public function addType(XSD\Schema\Schema $ref)
    {
        $this->types[] = $ref;
    }

    public function addMessage(Element\Message $message)
    {
        $this->messages[] = $message;
    }

    public function addSchema(Schema $schema)
    {
        $this->schemas[] = $schema;
    }

    public function addPortType(Element\PortType $portType)
    {
        $this->portTypes[] = $portType;
    }

    public function addBinding(Element\Binding $binding)
    {
        $this->bindings[] = $binding;
    }

    /**
     * @return XSD\Schema\Schema[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @return Schema[]
     */
    public function getSchemas()
    {
        return $this->schemas;
    }

    /**
     * @return Element\Message[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @return Element\PortType[]
     */
    public function getPortTypes()
    {
        return $this->portTypes;
    }

    /**
     * @return Element\Binding[]
     */
    public function getBindings()
    {
        return $this->bindings;
    }
}
