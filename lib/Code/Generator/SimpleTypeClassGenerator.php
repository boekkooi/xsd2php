<?php
namespace Goetas\Xsd\XsdToPhp\Code\Generator;

use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\ClassGenerator as ZendClassGenerator;

class SimpleTypeClassGenerator extends ClassGenerator
{
    public function __construct($name = null, $namespaceName = null, $flags = null, $extends = null, $interfaces = [], $properties = [], $methods = [], $docBlock = null)
    {
        parent::__construct($name, $namespaceName, $flags, $extends, $interfaces, $properties, $methods, $docBlock);

        static::implementSimpleType($this, $name);
    }

    public static function implementSimpleType(ZendClassGenerator $class, $type)
    {
        $property = new PropertyGenerator('__value', null, PropertyGenerator::FLAG_PRIVATE);
        $property->setDocBlock(new DocBlockGenerator());
        $property->setDocBlock(
            (new DocBlockGenerator())
                ->setTag(new Tag\GenericTag('var', $type))
        );
        $class->addPropertyFromGenerator($property);

        $method = new MethodGenerator('__construct', ['value']);
        $method->setBody('$this->value($value);');
        $method->setDocBlock(
            (new DocBlockGenerator())
                ->setShortDescription('Construct')
                ->setTag(new Tag\ParamTag('$value', $type))
        );
        $class->addMethodFromGenerator($method);

        $method = new MethodGenerator('value', []);
        $method->setBody(implode("\n", [
            'if ($args = func_get_args()) {',
            '    $this->__value = $args[0];',
            '}',
            'return $this->__value;'
        ]));
        $method->setDocBlock(
            (new DocBlockGenerator())
                ->setShortDescription('Gets or sets the inner value')
                ->setTag(new Tag\GenericTag(
                    'param',
                    sprintf('%s ...$value', $type)
                ))
                ->setTag(new Tag\ReturnTag($type))
        );
        $class->addMethodFromGenerator($method);

        $method = new MethodGenerator('__toString');
        $method->setBody('return (string)$this->__value;');
        $method->setDocBlock(
            (new DocBlockGenerator())
                ->setShortDescription('Gets a string value')
                ->setTag(new Tag\ReturnTag('string'))
        );
        $class->addMethodFromGenerator($method);

    }
}
