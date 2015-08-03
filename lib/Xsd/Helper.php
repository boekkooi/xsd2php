<?php
namespace Goetas\Xsd\XsdToPhp\Xsd;

use Goetas\XML\XSDReader\Schema\Element\ElementSingle;
use Goetas\XML\XSDReader\Schema\Type\ComplexType;
use Goetas\XML\XSDReader\Schema\Type\SimpleType;
use Goetas\XML\XSDReader\Schema\Type\Type;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
final class Helper
{
    private function __construct()
    {
    }

    /**
     * @param Type $type
     * @return boolean
     */
    public static function isArrayType(Type $type)
    {
        return $type instanceof SimpleType && $type->getList() instanceof SimpleType;
    }

    /**
     * @param Type $type
     * @return \Goetas\XML\XSDReader\Schema\Type\Type|null
     */
    public static function getArrayType(Type $type)
    {
        if ($type instanceof SimpleType) {
            return $type->getList();
        }

        return null;
    }

    /**
     * @param Type $type
     * @return ElementSingle|null
     */
    public static function isArrayNestedElement(Type $type)
    {
        if ($type instanceof ComplexType && ! $type->getParent() && ! $type->getAttributes() && count($type->getElements()) === 1) {
            $elements = $type->getElements();
            return self::isArrayElement(reset($elements));
        }

        return false;
    }

    /**
     * @param Type $type
     * @return ElementSingle|null
     */
    public static function getArrayNestedElement(Type $type)
    {
        if ($type instanceof ComplexType && ! $type->getParent() && ! $type->getAttributes() && count($type->getElements()) === 1) {
            $elements = $type->getElements();
            return self::getArrayElement(reset($elements));
        }

        return null;
    }

    /**
     * @param mixed $element
     * @return boolean
     */
    public static function isArrayElement($element)
    {
        return (
            $element instanceof ElementSingle &&
            ($element->getMax() > 1 || $element->getMax() === - 1)
        );
    }

    /**
     * @param mixed $element
     * @return ElementSingle|null
     */
    public static function getArrayElement($element)
    {
        return self::isArrayElement($element) ? $element : null;
    }
}
