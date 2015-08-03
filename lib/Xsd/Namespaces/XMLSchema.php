<?php
namespace Goetas\Xsd\XsdToPhp\Xsd\Namespaces;

use Goetas\Xsd\XsdToPhp\Converter\Configuration;

final class XMLSchema
{
    /**
     * @param Configuration $config
     */
    public static function addAliases(Configuration $config)
    {
        $config->addNamespace('http://www.w3.org/2001/XMLSchema', '');

        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "gYearMonth", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "gMonthDay", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "gMonth", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "gYear", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "NMTOKEN", 'string');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "NMTOKENS", 'string');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "QName", 'string');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "NCName", 'string');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "decimal", 'float');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "float", 'float');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "double", 'float');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "string", 'string');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "normalizedString", 'string');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "integer", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "int", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "unsignedInt", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "negativeInteger", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "positiveInteger", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "nonNegativeInteger", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "nonPositiveInteger", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "long", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "unsignedLong", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "short", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "boolean", 'boolean');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "nonNegativeInteger", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "positiveInteger", 'integer');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "language", 'string');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "token", 'string');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "anyURI", 'string');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "byte", 'string');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "duration", 'DateInterval');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "ID", 'string');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "IDREF", 'string');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "IDREFS", 'string');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "Name", 'string');
        $config->addTypeAlias("http://www.w3.org/2001/XMLSchema", "NCName", 'string');
    }
}
