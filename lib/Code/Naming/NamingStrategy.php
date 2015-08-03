<?php
namespace Goetas\Xsd\XsdToPhp\Code\Naming;

use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Item;

interface NamingStrategy
{
    public function getTypeName($rawName);

    public function getAnonymousTypeName($baseName);

    public function getItemName($rawName);
}
