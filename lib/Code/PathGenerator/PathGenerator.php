<?php
namespace Goetas\Xsd\XsdToPhp\Code\PathGenerator;

interface PathGenerator
{
    /**
     * Retrieve the path for the given namespace and name combination.
     *
     * @param string $namespace
     * @param string$name
     * @return string
     */
    public function getPath($namespace, $name);
}
