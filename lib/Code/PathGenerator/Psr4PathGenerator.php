<?php
namespace Goetas\Xsd\XsdToPhp\Code\PathGenerator;

use Goetas\Xsd\XsdToPhp\Exception\PathGeneratorException;

abstract class Psr4PathGenerator implements PathGenerator
{
    protected $namespaces = array();

    public function __construct(array $namespaces)
    {
        foreach ($namespaces as $namespace => $dir) {
            $this->addNamespace($namespace, $dir);
        }
    }

    /**
     * Find a related namespace.
     *
     * @param string $namespace
     * @return string[] list($namespace, $directory)
     * @throws PathGeneratorException
     */
    protected function findRelatedNamespace($namespace)
    {
        if (isset($this->namespaces[$namespace])) {
            return [$namespace, $this->namespaces[$namespace]];
        }

        foreach ($this->namespaces as $targetNamespace => $dir) {
            if (substr($namespace, 0, strlen($targetNamespace)) !== $targetNamespace) {
                continue;
            }

            return [$targetNamespace, $dir];
        }

        throw new PathGeneratorException(sprintf(
            'No directory registered for namespace %s',
            $namespace
        ));
    }

    /**
     * @param string $namespace
     * @param string $directory
     * @throws PathGeneratorException
     */
    protected function addNamespace($namespace, $directory)
    {
        $namespace = trim($namespace, '\\') . '\\';
        if (!is_dir($directory)) {
            throw new PathGeneratorException(sprintf(
                "The folder '%s' does not exist.",
                $directory
            ));
        }
        if (!is_writable($directory)) {
            throw new PathGeneratorException(sprintf(
                "The folder '%s' is not writable.",
                $directory
            ));
        }

        $this->namespaces[$namespace] = $directory;
    }
}
