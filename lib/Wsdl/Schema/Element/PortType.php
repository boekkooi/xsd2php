<?php

namespace Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element;

/**
 * XSD Type: tPortType
 */
class PortType
{
    /**
     * @var string $name
     */
    private $name;

    /**
     * @var Operation[] $operation
     */
    private $operation = [];

    /**
     * PortType constructor.
     *
     * @param string $name
     * @param array $operation
     */
    public function __construct($name, array $operation = array())
    {
        $this->name = $name;
        $this->operation = $operation;
    }

    /**
     * Gets as name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Adds as operation
     *
     * @return self
     * @param Operation $operation
     */
    public function addOperation(Operation $operation)
    {
        $this->operation[] = $operation;
        return $this;
    }

    /**
     * Gets as operation
     *
     * @return Operation[]
     */
    public function getOperations()
    {
        return $this->operation;
    }
}
