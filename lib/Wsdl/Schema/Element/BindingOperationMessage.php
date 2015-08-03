<?php

namespace Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element;

/**
 * XSD Type: tBindingOperationMessage
 */
class BindingOperationMessage
{
    /**
     * @var string|null $name
     */
    private $name;

    /**
     * BindingOperationMessage constructor.
     * @param string|null $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Gets as name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }
}
