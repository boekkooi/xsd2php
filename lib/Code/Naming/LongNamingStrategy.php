<?php
namespace Goetas\Xsd\XsdToPhp\Code\Naming;

use Doctrine\Common\Inflector\Inflector;
use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Item;

class LongNamingStrategy implements NamingStrategy
{
    public function getTypeName($rawName)
    {
        return Inflector::classify($rawName) . "Type";
    }

    public function getAnonymousTypeName($baseName)
    {
        return Inflector::classify($baseName) . "AnonymousType";
    }

    public function getItemName($rawName)
    {
        return Inflector::classify($rawName);
    }
}
