<?php

namespace Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element;

use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Type\QName;

/**
 * XSD Type: tBinding
 */
class Binding
{
    /**
     * @var string $name
     */
    private $name;

    /**
     * @var QName $type
     */
    private $type;

    /**
     * @var BindingOperation[] $operation
     */
    private $operation = [];

    /**
     * Binding constructor.
     *
     * @param string $name
     * @param QName $type
     * @param array $operation
     */
    public function __construct($name, QName $type, array $operation = [])
    {
        $this->name = $name;
        $this->type = $type;
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
     * Gets as type
     *
     * @return QName
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Adds as operation
     *
     * @param BindingOperation $operation
     * @return self
     */
    public function addOperation(BindingOperation $operation)
    {
        $this->operation[] = $operation;
        return $this;
    }

    /**
     * Gets as operation
     *
     * @return BindingOperation[]
     */
    public function getOperation()
    {
        return $this->operation;
    }
}
