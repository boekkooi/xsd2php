<?php
namespace Goetas\Xsd\XsdToPhp\Code\Generator\Property;

use Goetas\XML\XSDReader\Schema\Attribute\AttributeItem;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeSingle;
use Goetas\XML\XSDReader\Schema\Item;

class AttributePropertyGenerator extends PropertyGenerator
{
    public function __construct(AttributeItem $attribute, $type)
    {
        if (!$attribute instanceof Item) {
            throw new \InvalidArgumentException();
        }

        $flags = self::FLAG_PROTECTED;
        if ($attribute instanceof AttributeSingle && $attribute->getUse() === AttributeSingle::USE_REQUIRED) {
            $flags = $flags | self::FLAG_NOTNULL;
        }

        parent::__construct(
            $attribute->getName(),
            $type,
            $flags
        );

        $this->getDocBlock()
            ->setLongDescription($attribute->getDoc());
    }
}
