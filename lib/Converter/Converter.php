<?php
namespace Goetas\Xsd\XsdToPhp\Converter;

interface Converter
{
    /**
     * Convert a set of schema's.
     *
     * @param array $schemas
     * @return mixed
     */
    public function convert(array $schemas);

    /**
     * Return the configuration used by the converter.
     *
     * @return Configuration
     */
    public function getConfiguration();
}
