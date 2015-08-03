<?php
namespace Goetas\Xsd\XsdToPhp\Xsd\Namespaces;

use Goetas\Xsd\XsdToPhp\Converter\Configuration;

final class MicrosoftSerialization
{
    /**
     * @param Configuration $config
     */
    public static function addAliases(Configuration $config)
    {
        $config->addNamespace('http://schemas.microsoft.com/2003/10/Serialization/', '');

        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "anyType", 'mixed');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "anyURI", 'string');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "base64Binary", 'string');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "boolean", 'boolean');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "byte", 'string');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "dateTime", 'DateTime');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "decimal", 'float');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "double", 'float');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "float", 'float');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "int", 'integer');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "long", 'integer');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "QName", 'string');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "short", 'integer');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "string", 'string');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "unsignedByte", 'integer');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "unsignedInt", 'integer');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "unsignedLong", 'integer');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "unsignedShort", 'integer');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "char", 'string');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "duration", 'DateInterval');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "guid", 'string');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "FactoryType", 'string');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "ID", 'string');
        $config->addTypeAlias("http://schemas.microsoft.com/2003/10/Serialization/", "IDREF", 'string');
    }
}
