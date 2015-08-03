<?php
namespace Goetas\Xsd\XsdToPhp\Code\Generator;

use Goetas\XML\XSDReader\Schema\Type\SimpleType;
use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\Xsd\XsdToPhp\Converter\Configuration;
use Goetas\Xsd\XsdToPhp\Xsd\Helper;
use Zend\Code\Generator\DocBlock\Tag;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
class TypeClassGenerator extends ClassGenerator
{
    const FLAG_ALIAS    = 0x08;

    public static function create(Configuration $configuration, Type $type, $force)
    {
        $xmlNs = $type->getSchema()->getTargetNamespace();
        $xmlName = $type->getName();

        // Resolve type information
        list($name, $ns) = $configuration->resolvePHPTypeName($xmlNs, $xmlName);
        $typeHasAlias = $configuration->hasTypeAlias($xmlNs, $xmlName);

        // Create instance
        $instance = new static($name, $ns);
        if ($typeHasAlias) {
            $instance->addFlag(self::FLAG_ALIAS);
        }

        if (
            $typeHasAlias ||
            $type instanceof SimpleType ||
            ((Helper::isArrayType($type) || Helper::isArrayNestedElement($type)) && !$force)
        ) {
            $instance->addFlag(self::FLAG_SKIP);
        }

        $instance->getDocBlock()
            ->setShortDescription(sprintf('Class representing %s', $xmlName))
            ->setLongDescription($type->getDoc())
            ->setTag(new Tag\GenericTag('see', $xmlNs.'#'.$xmlName));

        return $instance;
    }

    public function hasAlias()
    {
        return (bool) ($this->flags & self::FLAG_ALIAS);
    }
}
