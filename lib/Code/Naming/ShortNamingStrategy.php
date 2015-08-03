<?php
namespace Goetas\Xsd\XsdToPhp\Code\Naming;

use Doctrine\Common\Inflector\Inflector;
use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Item;

class ShortNamingStrategy implements NamingStrategy
{
    public function getTypeName($rawName)
    {
        $name = Inflector::classify($rawName);
        if ($name && substr($name, - 4) !== 'Type') {
            $name .= "Type";
        }
        return $name;
    }

    public function getAnonymousTypeName($baseName)
    {
        return Inflector::classify($baseName) . "AType";
    }

    public function getItemName($rawName)
    {
        return Inflector::classify($rawName);
    }
}
