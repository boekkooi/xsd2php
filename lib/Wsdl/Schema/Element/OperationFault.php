<?php

namespace Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element;

use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Type\QName;

/**
 * XSD Type: tFault
 */
class OperationFault
{
    /**
     * @var string $name
     */
    private $name;

    /**
     * @var QName $message
     */
    private $message;

    /**
     * OperationFault constructor.
     *
     * @param string $name
     * @param QName $message
     */
    public function __construct($name, $message)
    {
        $this->name = $name;
        $this->message = $message;
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
     * Gets as message
     *
     * @return QName
     */
    public function getMessage()
    {
        return $this->message;
    }
}
