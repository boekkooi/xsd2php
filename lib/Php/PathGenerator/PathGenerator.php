<?php
namespace Goetas\Xsd\XsdToPhp\Php\PathGenerator;

use Goetas\Xsd\XsdToPhp\Code\PathGenerator\PathGenerator as CodePathGenerator;
use Zend\Code\Generator\ClassGenerator;

interface PathGenerator extends CodePathGenerator
{
    public function getPathByClassGenerator(ClassGenerator $php);
}
