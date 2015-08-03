<?php
namespace Goetas\Xsd\XsdToPhp\Wsdl;

use Goetas\XML\XSDReader as XSD;

/**
 * TODO remove this once we have $knownSchemas in the @see \Goetas\XML\XSDReader\SchemaReader
 */
class XsdSchemaReader extends XSD\SchemaReader
{
    public function readNode(\DOMNode $node, $file = 'schema.xsd', array $knownSchemas = array())
    {
        if (empty($knownSchemas)) {
            return parent::readNode($node, $file);
        }

        // Reflection for `loadedFiles` because it's private
        $loadedFilesRefl = new \ReflectionProperty(XSD\SchemaReader::class, 'loadedFiles');
        $loadedFilesRefl->setAccessible(true);

        // Reflection for `schemaNode` because it's private
        $schemaNodeRefl = new \ReflectionMethod(XSD\SchemaReader::class, 'schemaNode');
        $schemaNodeRefl->setAccessible(true);

        $loadedFiles = $loadedFilesRefl->getValue($this);
        $loadedFiles[$file] = $rootSchema = new XSD\Schema\Schema();
        $loadedFilesRefl->setValue($this, $loadedFiles);

        $rootSchema->addSchema($this->getGlobalSchema());
        foreach ($knownSchemas as $knownSchema) {
            $rootSchema->addSchema($knownSchema);
        }

        $callbacks = $schemaNodeRefl->invoke($this, $rootSchema, $node);

        foreach ($callbacks as $callback) {
            call_user_func($callback);
        }

        return $rootSchema;
    }
}
