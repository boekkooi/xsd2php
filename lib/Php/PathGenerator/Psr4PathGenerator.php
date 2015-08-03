<?php
namespace Goetas\Xsd\XsdToPhp\Php\PathGenerator;

use Goetas\Xsd\XsdToPhp\Exception\PathGeneratorException;
use Goetas\Xsd\XsdToPhp\Code\PathGenerator\Psr4PathGenerator as CodePsr4PathGenerator;
use Zend\Code\Generator\ClassGenerator;

class Psr4PathGenerator extends CodePsr4PathGenerator implements PathGenerator
{
    /**
     * @inheritdoc
     */
    public function getPath($namespace, $name)
    {
        $namespace = trim(trim($namespace), '\\')  . '\\';

        list($targetNamespace, $dir) = $this->findRelatedNamespace($namespace);

        $subDirectory = str_replace('\\', '/', substr($namespace, strlen($targetNamespace)));
        $dir = rtrim($dir, '/') . '/' . $subDirectory;
        if (is_dir($dir) || mkdir($dir, 0777, true)) {
            return rtrim($dir, '/') . '/' .  $name . ".php";
        }

        throw new PathGeneratorException(sprintf(
            'Can\'t create the \'%s\' directory',
            $dir
        ));
    }

    /**
     * @inheritdoc
     */
    public function getPathByClassGenerator(ClassGenerator $php)
    {
        return $this->getPath($php->getNamespaceName(), $php->getName());
    }
}
