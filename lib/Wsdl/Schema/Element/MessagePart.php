<?php

namespace Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element;

use Goetas\Xsd\XsdToPhp\Wsdl\Schema\Type\QName;

/**
 * Class representing TPartType
 *
 *
 * XSD Type: tPart
 */
class MessagePart
{
    /**
     * @var string $name
     */
    private $name;

    /**
     * @var QName|null $element
     */
    private $element = null;

    /**
     * @var QName|null $type
     */
    private $type = null;

    /**
     * MessagePart constructor.
     * @param string $name
     * @param string|null $element
     * @param string|null $type
     */
    public function __construct($name, QName $element = null, QName $type = null)
    {
        $this->name = $name;
        $this->element = $element;
        $this->type = $type;
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
     * Gets as element
     *
     * @return QName
     */
    public function getElement()
    {
        return $this->element;
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
}
