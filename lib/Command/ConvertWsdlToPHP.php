<?php
namespace Goetas\Xsd\XsdToPhp\Command;

use Goetas\XML\XSDReader as XSD;
use Goetas\Xsd\XsdToPhp\Converter\Configuration;
use Goetas\Xsd\XsdToPhp\Converter\Converter;
use Goetas\Xsd\XsdToPhp\Php\PathGenerator\Psr4PathGenerator;
use Goetas\Xsd\XsdToPhp\Wsdl\SchemaReader;
use Goetas\Xsd\XsdToPhp\Wsdl\SoapClientConverter;
use Goetas\Xsd\XsdToPhp\Wsdl\XsdSchemaReader;
use Goetas\Xsd\XsdToPhp\Xsd\Namespaces\MicrosoftSerialization;
use Symfony\Component\Console;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertWsdlToPHP extends ConvertToPHP
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('convert:wsdl:php');
        $this->setDescription('Convert WSDL definitions into PHP classes');
        $this->getDefinition()->getOption('naming-strategy')->setDefault('long');
//        $this->addOption('client-dest', null, InputOption::VALUE_REQUIRED, 'Where place the generated client files? Syntax: <info>PHP-namespace+class;destination-directory</info>');
    }

    protected function getConverter(Configuration $configuration)
    {
        return new SoapClientConverter(
            parent::getConverter($configuration)
        );
    }

    /**
     * @param Converter $converter
     * @param \Goetas\Xsd\XsdToPhp\Wsdl\Schema\Schema[] $wsdlSchemas
     * @param OutputInterface $output
     */
    protected function convert(Converter $converter, array $wsdlSchemas, OutputInterface $output)
    {
        $pathGenerator = new Psr4PathGenerator($converter->getConfiguration()->getNamespaceDestinations());

        // Generate the soap clients
        $classes = $converter->convert($wsdlSchemas);

        // Generate clients
        $this->generateFiles($classes, $pathGenerator, $output);
    }

    /**
     * @inheritdoc
     *
     * @return \Goetas\Xsd\XsdToPhp\Wsdl\Schema\Schema[]
     */
    protected function readSchemaSources(array $src, Configuration $configuration, Console\Output\OutputInterface $output)
    {
        $output->writeln("Reading files:");

        $reader = new SchemaReader(
            new XsdSchemaReader()
        );
        $schemas = array();
        foreach ($src as $file) {
            $output->writeln("  <comment>$file</comment>");

            $schema = $reader->readFile($file);

            $schemas[spl_object_hash($schema)] = $schema;
        }
        return $schemas;
    }

    /**
     * @inheritdoc
     */
    protected function initConfiguration(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $configuration = parent::initConfiguration($input, $output);
        MicrosoftSerialization::addAliases($configuration);
        return $configuration;
    }
}
