<?php

namespace Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element;

/**
 * XSD Type: tImport
 */
class Import
{
    /**
     * @var string $namespace
     */
    private $namespace;

    /**
     * @var string $location
     */
    private $location;

    /**
     * Import constructor.
     *
     * @param string $namespace
     * @param string $location
     */
    public function __construct($namespace, $location)
    {
        $this->namespace = $namespace;
        $this->location = $location;
    }

    /**
     * Gets as namespace
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Gets as location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }
}
