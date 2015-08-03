<?php

namespace Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element;

/**
 * XSD Type: tBindingOperationFault
 */
class BindingOperationFault
{
    /**
     * @var string $name
     */
    private $name;

    /**
     * BindingOperationFault constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
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
}
