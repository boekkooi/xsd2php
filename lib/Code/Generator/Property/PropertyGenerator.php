<?php
namespace Goetas\Xsd\XsdToPhp\Code\Generator\Property;

use Doctrine\Common\Inflector\Inflector;
use Goetas\Xsd\XsdToPhp\Code\Generator\TypeHelper;
use Zend\Code\Generator\DocBlock\Tag\GenericTag;
use Goetas\Xsd\XsdToPhp\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator as ZendPropertyGenerator;
use Zend\Code\Generator\PropertyValueGenerator;
use Zend\Code\Generator\ValueGenerator;

abstract class PropertyGenerator extends ZendPropertyGenerator
{
    const FLAG_NOTNULL    = 0x80;

    /**
     * @var string
     */
    private $type;
    /**
     * @var GenericTag
     */
    private $typeTag;

    public function __construct($name = null, $type = null, $flags = self::FLAG_PRIVATE)
    {
        parent::__construct($name, null, $flags);

        $this->setDocBlock(new DocBlockGenerator());
        $this->setType($type);
    }

    public function setType($type)
    {
        $this->type = TypeHelper::resolveType($type);

        if ($this->typeTag === null) {
            $this->typeTag = new GenericTag('var');
            $this->getDocBlock()->setTag($this->typeTag);
        }
        $this->typeTag->setContent(TypeHelper::docBlockType($this->type, $this->isNullable()));

        if (TypeHelper::isArrayType($this->type)) {
            $this->setDefaultValue([], PropertyValueGenerator::TYPE_ARRAY, PropertyValueGenerator::OUTPUT_SINGLE_LINE);
        }
    }

    /**
     * Indicated that the property is nullable
     *
     * @return bool
     */
    public function isNullable()
    {
        return !((bool) ($this->flags & self::FLAG_NOTNULL));
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * @return MethodGenerator
     */
    public function generateSetter()
    {
        $name = $this->getName();
        if (TypeHelper::isArrayType($this->getType())) {
            $name = Inflector::pluralize($name);
        }

        $method = new MethodGenerator(
            sprintf('set%s', Inflector::classify($name)),
            [ new ParameterGenerator(
                $this->getName(),
                TypeHelper::typeHintType($this->getType()),
                ($this->isNullable() ?  new ValueGenerator() : null)
            ) ]
        );

        if (TypeHelper::isArrayType($this->getType())) {
            $method->setBody(sprintf(
                implode("\n", array(
                    'foreach ($%1$s as $item) {',
                    '    $this->add%2$s($item);',
                    '}',
                    'return $this;'
                )),
                $this->getName(),
                Inflector::classify(Inflector::singularize($this->getName()))
            ));
        } else {
            $method->setBody(implode("\n", array(
                sprintf('$this->%1$s = $%1$s;', $this->getName()),
                'return $this;'
            )));
        }

        $method->setDocBlock(
            (new DocBlockGenerator())
                ->setShortDescription(sprintf(sprintf('Sets the %s.', $name)))
                ->setTag(new Tag\ParamTag($this->getName(), TypeHelper::docBlockType($this->type, $this->isNullable())))
                ->setTag(new Tag\ReturnTag('$this'))
        );

        return $method;
    }

    /**
     * @return MethodGenerator
     */
    public function generateGetter()
    {
        $name = $this->getName();
        if (TypeHelper::isArrayType($this->getType())) {
            $name = Inflector::pluralize($name);
        }

        $method = new MethodGenerator(
            sprintf('get%s', Inflector::classify($name))
        );
        $method->setBody(sprintf(
            'return $this->%1$s;', $this->getName()
        ));
        $method->setDocBlock(
            (new DocBlockGenerator())
                ->setShortDescription(sprintf(sprintf('Gets the %s.', $name)))
                ->setTag(new Tag\ReturnTag(TypeHelper::docBlockType($this->type, $this->isNullable())))
        );

        return $method;
    }

    /**
     * Array add method.
     * @return MethodGenerator
     */
    public function generateArrayAdd()
    {
        $singleType = substr($this->getType(), 0, -2);
        $name = Inflector::singularize($this->getName());

        $method = new MethodGenerator(
            sprintf('add%s', Inflector::classify($name)),
            [ new ParameterGenerator($this->getName(), TypeHelper::typeHintType($singleType)) ]
        );
        $method->setBody(sprintf(
            implode("\n", [
                '$this->%1$s[] = $%1$s;',
                'return $this;',
            ]),
            $this->getName()
        ));

        $name = Inflector::singularize($this->getName());
        $method->setDocBlock(
            (new DocBlockGenerator())
                ->setShortDescription(sprintf('Adds an %s.', $name))
                ->setTag(new Tag\ParamTag(
                    $this->getName(),
                    TypeHelper::docBlockType($singleType),
                    sprintf('The %s to add.', $name)
                ))
                ->setTag(new Tag\ReturnTag('$this'))
        );

        return $method;
    }

    /**
     * Array add method.
     * @return MethodGenerator
     */
    public function generateArraySet()
    {
        $singleType = substr($this->getType(), 0, -2);
        $name = Inflector::singularize($this->getName());

        $method = new MethodGenerator(
            sprintf('set%s', Inflector::classify($name)),
            [
                new ParameterGenerator('key'),
                new ParameterGenerator($name, TypeHelper::typeHintType($singleType))
            ]
        );
        $method->setBody(sprintf(
            implode("\n", [
                '$this->%1$s[$key] = $%2$s;',
                'return $this;',
            ]),
            $this->getName(),
            $name
        ));

        $name = Inflector::singularize($this->getName());
        $method->setDocBlock(
            (new DocBlockGenerator())
                ->setShortDescription(sprintf('Sets an %s at the specified key/index.', $name))
                ->setTag(new Tag\ParamTag(
                    'key',
                    ['string', 'integer'],
                    sprintf('The key/index of the %s to set.', $name)
                ))
                ->setTag(new Tag\ParamTag(
                    $name,
                    TypeHelper::docBlockType($singleType),
                    sprintf('The %s to set.', $name)
                ))
                ->setTag(new Tag\ReturnTag('$this'))
        );

        return $method;
    }

    /**
     * Array add method.
     * @return MethodGenerator
     */
    public function generateArrayRemove()
    {
        $singleType = substr($this->getType(), 0, -2);
        $name = Inflector::singularize($this->getName());

        $method = new MethodGenerator(
            sprintf('remove%s', Inflector::classify($name)),
            [ new ParameterGenerator('key') ]
        );
        $method->setBody(sprintf(
            implode("\n", [
                'if (!isset($this->%1$s[$key]) && !array_key_exists($key, $this->%1$s)) {',
                '    return null;',
                '}',
                '$removed = $this->%1$s[$key];',
                'unset($this->%1$s[$key]);',
                'return $removed;',
            ]),
            $this->getName()
        ));

        $method->setDocBlock(
            (new DocBlockGenerator())
                ->setShortDescription(sprintf('Removes the %s at the specified key/index.', $name))
                ->setTag(new Tag\ParamTag(
                    'key',
                    ['string', 'integer'],
                    sprintf('The kex/index of the %s to remove', $name)
                ))
                ->setTag(new Tag\ReturnTag(
                    [TypeHelper::docBlockType($singleType), 'null'],
                    sprintf('The removed %s or NULL.', $name)
                ))
        );

        return $method;
    }

    /**
     * Array add method.
     * @return MethodGenerator
     */
    public function generateArrayIndexOf()
    {
        $singleType = substr($this->getType(), 0, -2);
        $name = Inflector::singularize($this->getName());

        $method = new MethodGenerator(
            sprintf('indexOf%s', Inflector::classify($name)),
            [ new ParameterGenerator($this->getName()) ]
        );
        $method->setBody(sprintf(
            'return array_search($%1$s, $this->%1$s, true);',
            $this->getName()
        ));

        $method->setDocBlock(
            (new DocBlockGenerator())
                ->setShortDescription(sprintf('Removes the %1$s at the specified key/index.', $name))
                ->setTag(new Tag\ParamTag(
                    $this->getName(),
                    [ TypeHelper::docBlockType($singleType) ],
                    sprintf('The %1$s to search for.', $name)
                ))
                ->setTag(new Tag\ReturnTag(
                    ['string', 'integer', 'bool'],
                    sprintf('The key/index of the %1$s or FALSE if the %1$s was not found.', $name)
                ))
        );

        return $method;
    }
}
