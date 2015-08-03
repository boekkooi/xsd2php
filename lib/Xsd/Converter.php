<?php
namespace Goetas\Xsd\XsdToPhp\Xsd;

use Goetas\Xsd\XsdToPhp\Converter\Configuration;
use Goetas\Xsd\XsdToPhp\Converter\Converter as ConverterInterface;
use Goetas\XML\XSDReader\Schema\Schema;
use Goetas\XML\XSDReader\Schema\Type\Type;

abstract class Converter implements ConverterInterface
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Constructor.
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;

        $configuration->addNamespace('http://www.w3.org/XML/1998/namespace', '');
        Namespaces\XMLSchema::addAliases($configuration);
    }

    /**
     * Convert the xsd schema's.
     *
     * @param Schema[] $schemas
     * @return mixed
     */
    abstract public function convert(array $schemas);

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function getTypeAlias(Type $type, Schema $schema = null)
    {
        $schema = $schema ?: $type->getSchema();

        return $this->getConfiguration()
            ->getTypeAlias(
                $schema->getTargetNamespace(),
                $type->getName()
            );
    }

    /**
     * @return \Goetas\Xsd\XsdToPhp\Code\Naming\NamingStrategy
     */
    protected function getNamingStrategy()
    {
        return $this->getConfiguration()->getNamingStrategy();
    }
}
