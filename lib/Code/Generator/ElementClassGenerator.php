<?php
namespace Goetas\Xsd\XsdToPhp\Code\Generator;

use Goetas\XML\XSDReader\Schema\Element\ElementItem;
use Goetas\XML\XSDReader\Schema\Item;
use Goetas\Xsd\XsdToPhp\Converter\Configuration;
use Zend\Code\Generator\DocBlock\Tag;

class ElementClassGenerator extends ClassGenerator
{
    public function __construct($name = null, $namespaceName = null, $flags = null, $extends = null, $interfaces = [], $properties = [], $methods = [], $docBlock = null)
    {
        parent::__construct($name, $namespaceName, $flags, $extends, $interfaces, $properties, $methods, $docBlock);
    }

    public static function create(Configuration $configuration, ElementItem $element)
    {
        if (!$element instanceof Item) {
            throw new \InvalidArgumentException();
        }

        $xmlNs = $element->getSchema()->getTargetNamespace();
        $xmlName = $element->getName();

        list($name, $ns) = $configuration->resolvePHPItemName($xmlNs, $xmlName);

        // Create instance
        $instance = new static($name, $ns);
        $instance->getDocBlock()
            ->setShortDescription(sprintf('Class representing %s', $xmlName))
            ->setLongDescription($element->getDoc())
            ->setTag(new Tag\GenericTag('see', $xmlNs.'#'.$xmlName));

        return $instance;
    }
}
