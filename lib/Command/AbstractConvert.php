<?php
namespace Goetas\Xsd\XsdToPhp\Command;

use Exception;
use Goetas\Xsd\XsdToPhp\Converter\Configuration;
use Goetas\Xsd\XsdToPhp\Converter\Converter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;
use Goetas\XML\XSDReader\SchemaReader;
use Goetas\Xsd\XsdToPhp\Code\Naming\ShortNamingStrategy;
use Goetas\Xsd\XsdToPhp\Code\Naming\LongNamingStrategy;

abstract class AbstractConvert extends Console\Command\Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDefinition(array(
            new InputArgument('src', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Where is located your XSD definitions'),
            new InputOption('ns-map', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'How to map XML namespaces to PHP namespaces? Syntax: <info>XML-namespace;PHP-namespace</info>'),
            new InputOption('ns-dest', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Where place the generated files? Syntax: <info>PHP-namespace;destination-directory</info>'),
            new InputOption('alias-map', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'How to map XML namespaces into existing PHP classes? Syntax: <info>XML-namespace;XML-type;PHP-type</info>. '),
            new InputOption('naming-strategy', null, InputOption::VALUE_REQUIRED, 'The naming strategy for classes. short|long', 'short')
        ));
    }

    /**
     * @param Configuration $configuration
     * @return Converter
     */
    abstract protected function getConverter(Configuration $configuration);

    /**
     * @inheritdoc
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $configuration = $this->initConfiguration($input, $output);

        $src = $input->getArgument('src');
        $schemas = $this->readSchemaSources($src, $configuration, $output);

        $converter = $this->getConverter($configuration);
        if (!$converter instanceof Converter) {
            throw new \LogicException(sprintf('A converter must inherit from %s', AbstractConvert::class));
        }
        $this->convert($converter, $schemas, $output);

        return 0;
    }

    abstract protected function convert(Converter $converter, array $schemas, Console\Output\OutputInterface $output);

    /**
     * Read a set of schema sources.
     *
     * @param Console\Output\OutputInterface $output
     * @param string[] $src
     * @param Configuration $configuration
     * @return array
     * @throws \RuntimeException
     */
    protected function readSchemaSources(array $src, Configuration $configuration, Console\Output\OutputInterface $output)
    {
        $output->writeln("Reading files:");
        $reader = new SchemaReader();
        $schemas = array();
        foreach ($src as $file) {
            $output->writeln("  <comment>$file</comment>");

            $xml = new \DOMDocument('1.0', 'UTF-8');
            if (!$xml->load($file)) {
                throw new \RuntimeException("Can't load the schema '{$file}'");
            }


            if (!$configuration->hasNamespace($xml->documentElement->getAttribute("targetNamespace"))) {
                $output->writeln("  Skipping <comment>" . $xml->documentElement->getAttribute("targetNamespace") . "</comment>, can't find a PHP-equivalent namespace. Use --ns-map option?");
                continue;
            }

            $schema = $reader->readFile($file);

            $schemas[spl_object_hash($schema)] = $schema;
        }

        return $schemas;
    }

    /**
     * Initialize the converter configuration base on the provided input.
     *
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     * @return Configuration
     */
    protected function initConfiguration(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $config = new Configuration();

        $this->initNamingStrategy($config, $input);
        $this->initNamespaceMap($config, $input, $output);
        $this->initNamespaceDestinations($config, $input, $output);
        $this->initAliasMap($config, $input, $output);

        return $config;
    }

    /**
     * @param Configuration $config
     * @param Console\Input\InputInterface $input
     * @return LongNamingStrategy|ShortNamingStrategy
     */
    protected function initNamingStrategy(Configuration $config, Console\Input\InputInterface $input)
    {
        if ($input->getOption('naming-strategy') == 'short') {
            $config->setNamingStrategy(new ShortNamingStrategy());
        } elseif ($input->getOption('naming-strategy') == 'long') {
            $config->setNamingStrategy(new LongNamingStrategy());
        } else {
            throw new \InvalidArgumentException("Unsupported naming strategy");
        }
    }

    /**
     * @param Configuration $config
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     * @throws Exception
     */
    protected function initNamespaceMap(Configuration $config, Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $nsMap = $input->getOption('ns-map');
        if (!$nsMap) {
            throw new \RuntimeException(__CLASS__ . " requires at least one ns-map.");
        }

        $output->writeln("Namespaces:");
        foreach ($nsMap as $val) {
            if (substr_count($val, ';') !== 1) {
                throw new \InvalidArgumentException(sprintf(
                    "Invalid syntax for --ns-map=\"%s\"",
                    $val
                ));
            }
            list($xmlNs, $phpNs) = explode(";", $val, 2);

            $config->addNamespace($xmlNs, trim(strtr($phpNs, "./", "\\\\"), "\\") . '\\');

            $output->writeln("  XML namepsace: <comment>$xmlNs</comment> => PHP namepsace: <info>$phpNs</info>");
        }
    }

    /**
     * @param Configuration $config
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     */
    protected function initNamespaceDestinations(Configuration $config, Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $nsTarget = $input->getOption('ns-dest');
        if (!$nsTarget) {
            throw new \RuntimeException(__CLASS__ . " requires at least one ns-target.");
        }

        $output->writeln("Target directories:");
        foreach ($nsTarget as $val) {
            if (substr_count($val, ';') !== 1) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid syntax for --ns-dest="%s"',
                    $val
                ));
            }
            list($phpNs, $dir) = explode(";", $val, 2);
            $phpNs = strtr($phpNs, "./", "\\\\");

            $output->writeln("  PHP namepsace: <comment>" . strtr($phpNs, "\\", "/") . "</comment> => Destination directory: <info>$dir</info>");

            if (is_dir($dir)) {
                $config->addNamespaceDestination($phpNs, $dir);
                continue;
            }
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $questionHelper */
            $questionHelper = $this->getHelper('question');
            $create = $questionHelper->ask(
                $input,
                $output,
                new Console\Question\ConfirmationQuestion(
                    sprintf('The folder \'%s\' does not exist. Would you like to create it?', $dir)
                )
            );
            if ($create && @mkdir($dir, 0777, true)) {
                $config->addNamespaceDestination($phpNs, $dir);
                continue;
            }

            throw new \InvalidArgumentException(sprintf('The folder \'%s\' does not exist.', $dir));
        }
    }

    /**
     * @param Configuration $config
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     */
    protected function initAliasMap(Configuration $config, Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $arrayMap = $input->getOption('alias-map');
        if (!$arrayMap) {
            return;
        }

        $output->writeln("Aliases:");
        foreach ($arrayMap as $val) {
            if (substr_count($val, ';') !== 2) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid syntax for --alias-map="%s"',
                    $val
                ));
            }

            list($xmlNs, $name, $type) = explode(";", $val, 3);

            $config->addTypeAlias($xmlNs, $name, $type);

            $output->writeln("  XML Type: <comment>$xmlNs</comment>#<comment>$name</comment>  => PHP Class: <info>$type</info> ");
        }
    }
}
