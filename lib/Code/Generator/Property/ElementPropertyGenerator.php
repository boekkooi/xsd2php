<?php
namespace Goetas\Xsd\XsdToPhp\Code\Generator\Property;

use Doctrine\Common\Inflector\Inflector;
use Goetas\XML\XSDReader\Schema\Element\Element;
use Goetas\XML\XSDReader\Schema\Element\ElementItem;
use Goetas\XML\XSDReader\Schema\Item;

class ElementPropertyGenerator extends PropertyGenerator
{
    public function __construct(ElementItem $element, $type)
    {
        if (!$element instanceof Item) {
            throw new \InvalidArgumentException();
        }

        $flags = self::FLAG_PRIVATE;
        if (
            $element instanceof Element &&
            !$element->isNil() &&
            $element->getMin() >= 1
        ) {
            $flags = $flags | self::FLAG_NOTNULL;
        }

        parent::__construct(
            Inflector::camelize($element->getName()),
            $type,
            $flags
        );

        $this->getDocBlock()
            ->setLongDescription($element->getDoc());
    }
}
