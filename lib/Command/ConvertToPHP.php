<?php
namespace Goetas\Xsd\XsdToPhp\Command;

use Goetas\Xsd\XsdToPhp\Code\Generator\ClassGenerator;
use Goetas\Xsd\XsdToPhp\Converter\Configuration;
use Goetas\Xsd\XsdToPhp\Converter\Converter;
use Goetas\Xsd\XsdToPhp\Php\PathGenerator\PathGenerator;
use Goetas\Xsd\XsdToPhp\Php\PathGenerator\Psr4PathGenerator;
use Goetas\Xsd\XsdToPhp\Php\PhpConverter;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Code\Generator\FileGenerator;

class ConvertToPHP extends AbstractConvert
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('convert:php');
        $this->setDescription('Convert XSD definitions into PHP classes');
    }

    /**
     * @inheritdoc
     */
    protected function getConverter(Configuration $configuration)
    {
        return new PhpConverter($configuration);
    }

    /**
     * @inheritdoc
     */
    protected function convert(Converter $converter, array $schemas, OutputInterface $output)
    {
        $items = $converter->convert($schemas);

        $this->generateFiles(
            $items,
            new Psr4PathGenerator($converter->getConfiguration()->getNamespaceDestinations()),
            $output
        );
    }

    /**
     * @param OutputInterface $output
     * @param ClassGenerator[] $items
     * @param PathGenerator $pathGenerator
     */
    protected function generateFiles(array $items, PathGenerator $pathGenerator, OutputInterface $output)
    {
        /** @var \Symfony\Component\Console\Helper\ProgressHelper $progress */
        $progress = $this->getHelperSet()->get('progress');
        $progress->start($output, count($items));

        $output->writeln('Generating files:');
        foreach ($items as $item) {
            $progress->advance(1, true);
            $output->write(" Creating <info>" . $output->getFormatter()->escape($item->getNamespaceName() . '\\' . $item->getName()) . "</info>... ");
            $path = $pathGenerator->getPathByClassGenerator($item);

            $fileGen = new FileGenerator();
            $fileGen->setFilename($path);

            $fileGen->setClass($item);
            $fileGen->write();

            $output->writeln("done.");
        }
        $progress->finish();
    }
}
