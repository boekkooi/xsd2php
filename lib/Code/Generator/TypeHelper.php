<?php
namespace Goetas\Xsd\XsdToPhp\Code\Generator;

final class TypeHelper
{
    private function __construct() {}

    public static function isArrayType($type)
    {
        return (substr($type, -2) === '[]');
    }

    public static function resolveType($type)
    {
        $typeMap = array(
            'integer' => 'int',
            'boolean' => 'bool'
        );
        if (isset($typeMap[$type])) {
            return $typeMap[$type];
        }
        return $type;
    }

    public static function isNativeType($type)
    {
        // Remove array notation/hint
        while (substr($type, -2) === '[]') {
            $type = substr($type, 0, -2);
        }

        return in_array($type, [
            'string',
            'int',
            'float',
            'bool',
            'mixed',
            'object',
            'resource',
            'callable'
        ]);
    }

    public static function docBlockType($type, $nullable = false)
    {
        $types = [];
        if ($type === null) {
            $types[] = 'mixed';
        } elseif (self::isNativeType($type)) {
            $types[] = $type;
        } else {
            $types[] = '\\' . $type;
        }

        if ($nullable) {
            $types[] = 'null';
        }

        return implode('|', $types);
    }

    public static function typeHintType($type)
    {
        if (self::isArrayType($type)) {
            return 'array';
        }
        if (self::isNativeType($type)) {
            return null;
        }

        return '\\' . $type;
    }
}
