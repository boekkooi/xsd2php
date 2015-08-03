<?php
namespace Goetas\Xsd\XsdToPhp\Code\Generator;

use Doctrine\Common\Inflector\Inflector;
use Zend\Code\Generator\ClassGenerator as ZendClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
class ClassGenerator extends ZendClassGenerator
{
    const FLAG_SKIP    = 0x04;

    /**
     * @var ClassGenerator|null
     */
    private $extendsClassGenerator;

    public function __construct($name = null, $namespaceName = null, $flags = null, $extends = null, $interfaces = [], $properties = [], $methods = [], $docBlock = null)
    {
        $docBlock = $docBlock ?: new DocBlockGenerator();
        parent::__construct($name, $namespaceName, $flags, $extends, $interfaces, $properties, $methods, $docBlock);
    }

    /**
     * Indicated that the class should not be generated.
     *
     * @return bool
     */
    public function skip()
    {
        return (bool) ($this->flags & self::FLAG_SKIP);
    }

    /**
     * Get the Fully-Qualified Class Name.
     *
     * @return string
     */
    public function getFQCN()
    {
        $ns = $this->getNamespaceName();
        return (!empty($ns) ? $ns . '\\' : '') . $this->getName();
    }

    /**
     * @param ClassGenerator $extendsClassGenerator
     * @return $this
     */
    public function setExtendsClassGenerator(ClassGenerator $extendsClassGenerator)
    {
        $this->extendsClassGenerator = $extendsClassGenerator;
        return $this;
    }

    /**
     * @return ClassGenerator|null
     */
    public function getExtendsClassGenerator()
    {
        return $this->extendsClassGenerator;
    }

    /**
     * @inheritdoc
     */
    public function addPropertyFromGenerator(PropertyGenerator $property)
    {
        $propertyName = $property->getName();
        if ($this->hasProperty($propertyName)) {
            unset($this->properties[$propertyName]);
        }

        return parent::addPropertyFromGenerator($property);
    }

    /**
     * Return a resolved ClassGenerator
     *
     * @return \Zend\Code\Generator\ClassGenerator
     */
    public function resolve()
    {
        $class = new ZendClassGenerator(
            $this->getName(),
            $this->getNamespaceName(),
            $this->flags,
            $this->getExtendedClass(),
            $this->getImplementedInterfaces(),
            $this->getProperties(),
            $this->getMethods(),
            $this->getDocBlock()
        );
        // TODO copy traits & uses

        $this->resolveConstructor($class);
        $this->resolveExtends($class);
        $this->resolvePropertyMethods($class);

        return $class;
    }

    protected function resolveExtends(ZendClassGenerator $class)
    {
        $extends = $this->extendsClassGenerator;
        if ($extends === null) {
            return;
        }

        if (($extendsSimple = $this->retrieveExtendedSimpleType()) !== null && $extends->skip()) {
            $class->setExtendedClass(null);
            if (!$this->hasProperty('__value')) {
                SimpleTypeClassGenerator::implementSimpleType($class, $extendsSimple->getName());
            }
        }

        if ($extends instanceof self) {
            if ($extends->skip()) {
                // TODO merge?
                $class->setExtendedClass(null);
            } elseif ($this->getName() === $extends->getName()) {
                $class->setExtendedClass($extends->getFQCN());
            } else {
                $extendsNamespace = $extends->getNamespaceName();
                if ($this->getNamespaceName() !== $extendsNamespace && !in_array($extendsNamespace, $class->getUses())) {
                    $class->addUse($extendsNamespace);
                }
                $class->setExtendedClass($extends->getName());
            }
        }
    }

    protected function resolvePropertyMethods(ZendClassGenerator $class)
    {
        $properties = $class->getProperties();
        foreach ($properties as $property) {
            if (!$property instanceof Property\PropertyGenerator) {
                continue;
            }

            $class->addMethodFromGenerator($property->generateGetter());
            $class->addMethodFromGenerator($property->generateSetter());

            if (TypeHelper::isArrayType($property->getType())) {
                $class->addMethodFromGenerator($property->generateArrayAdd());
                $class->addMethodFromGenerator($property->generateArraySet());
                $class->addMethodFromGenerator($property->generateArrayRemove());
                $class->addMethodFromGenerator($property->generateArrayIndexOf());
            }
        }
    }

    protected function retrieveExtendedSimpleType()
    {
        $extends = $this->getExtendsClassGenerator();
        while ($extends !== null) {
            if ($extends instanceof SimpleTypeClassGenerator) {
                return $extends;
            }

            $extends = $extends->getExtendsClassGenerator();
        }

        return null;
    }

    private function resolveConstructor(ZendClassGenerator $class)
    {
        $method = new MethodGenerator('__construct');
        $body = [];

        $properties = $class->getProperties();
        foreach ($properties as $property) {
            if (!$property instanceof Property\PropertyGenerator) {
                continue;
            }

            if ($property->isNullable()) {
                continue;
            }

            $name = $property->getName();
            if (TypeHelper::isArrayType($property->getType())) {
                $name = Inflector::pluralize($name);
            }

            $method->setParameter(new ParameterGenerator(
                $name,
                TypeHelper::typeHintType($property->getType())
            ));

            $body[] = sprintf(
                '$this->set%s = $%s;',
                Inflector::classify($name),
                $name
            );
        }
        $method->setBody(implode("\n", $body));

        $class->addMethodFromGenerator($method);
    }
}
