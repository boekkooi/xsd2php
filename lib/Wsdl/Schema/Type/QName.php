<?php
namespace Goetas\Xsd\XsdToPhp\Wsdl\Schema\Type;

use DOMElement;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
class QName
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string|null
     */
    private $namespace;
    /**
     * @var string|null
     */
    private $prefix;

    public function __construct($name, $namespace = null, $prefix = null)
    {
        $this->name = $name;
        $this->namespace = $namespace;
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return null|string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return null|string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * http://www.w3.org/TR/xmlschema-2/#QName
     * http://www.w3.org/TR/1999/REC-xml-names-19990114/#dt-qname
     */
    public static function create($value, DOMElement $node)
    {
        if (strpos($value, ':') === false) {
            return new self($value, $node->namespaceURI, null);
        }

        $parts = explode(':', $value);
        $namespace = $node->lookupNamespaceUri($parts[0]);
        if ($namespace === null) {
            $namespace = $node->ownerDocument->documentElement->getAttribute('targetNamespace');
        }
        return new self($parts[1], $namespace, $parts[0]);
    }
}
