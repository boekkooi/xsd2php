<?php
namespace Goetas\Xsd\XsdToPhp\Converter;

use Goetas\Xsd\XsdToPhp\Code\Naming\NamingStrategy;
use Goetas\Xsd\XsdToPhp\Code\Naming\ShortNamingStrategy;

class Configuration
{
    /**
     * @var NamingStrategy|null
     */
    private $namingStrategy;

        /**
     * @var array
     */
    private $phpNamespaceDestination;

    /**
     * @var array
     */
    private $namespaceMap;

    /**
     * @var array
     */
    private $namespaceTypeAliases;

    /**
     * @param NamingStrategy $namingStrategy
     */
    public function setNamingStrategy($namingStrategy)
    {
        $this->namingStrategy = $namingStrategy;
    }

    /**
     * @return NamingStrategy
     */
    public function getNamingStrategy()
    {
        return $this->namingStrategy ?: new ShortNamingStrategy();
    }

    /**
     * Add a destination for a PHP namespace.
     *
     * @param string $phpNamespace
     * @param string $path
     * @return $this
     */
    public function addNamespaceDestination($phpNamespace, $path)
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf(
                'The folder \'$dir\' does not exist.',
                $path
            ));
        }
        if (!is_writable($path)) {
            throw new \InvalidArgumentException(sprintf(
                'The folder \'$dir\' does not writable.',
                $path
            ));
        }

        $this->phpNamespaceDestination[trim($phpNamespace, '\\')] = $path;
    }

    /**
     * Indicates that a PHP namespace has a destination.
     *
     * @param string $phpNamespace
     * @return bool
     */
    public function hasNamespaceDestination($phpNamespace)
    {
        return isset($this->phpNamespaceDestination[$phpNamespace]);
    }

    /**
     * @return array Key: PHP namespace Value: $directoryPath
     */
    public function getNamespaceDestinations()
    {
        return $this->phpNamespaceDestination;
    }

    /**
     * Add a namespace map from the XML namespace to the PHP namespace.
     *
     * @param string $xmlNamespace
     * @param string $phpNamespace
     * @return $this
     */
    public function addNamespace($xmlNamespace, $phpNamespace)
    {
        $this->namespaceMap[$xmlNamespace] = trim($phpNamespace, '\\');
        return $this;
    }

    /**
     * Indicates that a XML namespace has a map to a PHP namespace.
     *
     * @param string $xmlNamespace
     * @return bool
     */
    public function hasNamespace($xmlNamespace)
    {
        return isset($this->namespaceMap[$xmlNamespace]);
    }

    /**
     * Indicated that the given xml namespace is exclude from being generated.
     *
     * @return boolean
     */
    public function isExcludedNamespace($xmlNamespace)
    {
        return (
            $this->hasNamespace($xmlNamespace) &&
            !$this->hasNamespaceDestination($this->getNamespace($xmlNamespace))
        );
    }

    /**
     * Retrieve a PHP namespace for the given XML namespace.
     *
     * @param string $xmlNamespace
     * @return bool
     */
    public function getNamespace($xmlNamespace)
    {
        return $this->hasNamespace($xmlNamespace) ? $this->namespaceMap[$xmlNamespace] : null;
    }

    /**
     * Add a php alias for a xml type.
     *
     * @param string $xmlNamespace
     * @param string $typeName
     * @param string|\Closure $handler
     */
    public function addTypeAlias($xmlNamespace, $typeName, $handler)
    {
        $this->namespaceTypeAliases[$xmlNamespace][$typeName] = $handler;
    }

    /**
     * Indicated that the given xml type has a known alias.
     *
     * @return boolean
     */
    public function hasTypeAlias($xmlNamespace, $typeName)
    {
        return isset($this->namespaceTypeAliases[$xmlNamespace][$typeName]);
    }

    /**
     * Retrieve the PHP type alias of a given XML namespace and type.
     *
     * @param string $xmlNamespace
     * @param string $typeName
     * @return mixed|null
     */
    public function getTypeAlias($xmlNamespace, $typeName)
    {
        if (!isset($this->namespaceTypeAliases[$xmlNamespace][$typeName])) {
            return null;
        }

        $handler = $this->namespaceTypeAliases[$xmlNamespace][$typeName];
        if ($handler instanceof \Closure) {
            return $handler($xmlNamespace, $typeName);
        }
        if (is_callable($handler)) {
            return call_user_func($handler, $xmlNamespace, $typeName);
        }
        if (is_string($handler)) {
            return $handler;
        }

        throw new \RuntimeException(sprintf(
            'Invalid handler provided for type %s in %s',
            $xmlNamespace,
            $typeName
        ));
    }

    /**
     * Retrieve a PHP name based on a given XML namespace and type
     *
     * @param string $xmlNamespace
     * @param string $typeName
     * @return array list($className, $namespace)
     */
    public function resolvePHPTypeName($xmlNamespace, $typeName)
    {
        $className = $this->getTypeAlias($xmlNamespace, $typeName);
        if ($className !== null) {
            if (($pos = strrpos($className, '\\')) !== false) {
                return [
                    substr($className, $pos + 1),
                    substr($className, 0, $pos)
                ];
            }
            return [
                $className,
                null
            ];
        }

        if (!$this->hasNamespace($xmlNamespace)) {
            throw new \InvalidArgumentException(sprintf(
                "Can't find a PHP namespace to '%s' namespace",
                $xmlNamespace
            ));
        }

        return [
            $this->getNamingStrategy()->getTypeName($typeName),
            $this->getNamespace($xmlNamespace)
        ];
    }

    public function resolvePHPItemName($xmlNamespace, $typeName)
    {
        if (!$this->hasNamespace($xmlNamespace)) {
            throw new \InvalidArgumentException(sprintf(
                "Can't find a PHP namespace to '%s' namespace",
                $xmlNamespace
            ));
        }

        return [
            $this->getNamingStrategy()->getItemName($typeName),
            $this->getNamespace($xmlNamespace)
        ];
    }
}
