<?php

namespace Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element;

use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Type\QName;

/**
 * XSD Type: tParam
 */
class OperationParam
{
    /**
     * @property string|null $name
     */
    private $name = null;

    /**
     * @property QName $message
     */
    private $message;

    /**
     * OperationParam constructor.
     * @param null $name
     * @param QName $message
     */
    public function __construct(QName $message, $name = null)
    {
        $this->message = $message;
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
