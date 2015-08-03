<?php

namespace Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element;

/**
 * XSD Type: tMessage
 */
class Message
{
    /**
     * @var string $name
     */
    private $name;

    /**
     * @var MessagePart[] $part
     */
    private $part;

    /**
     * Message constructor.
     *
     * @param string $name
     * @param MessagePart[] $part
     */
    public function __construct($name, array $part = array())
    {
        $this->name = $name;
        $this->part = $part;
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
     * Gets as part
     *
     * @return MessagePart[]
     */
    public function getParts()
    {
        return $this->part;
    }

    /**
     * Adds as part
     *
     * @return self
     * @param MessagePart $part
     */
    public function addPart(MessagePart $part)
    {
        $this->part[$part->getName()] = $part;
        return $this;
    }
}
