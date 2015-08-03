<?php
namespace Goetas\Xsd\XsdToPhp\Command;

use Goetas\Xsd\XsdToPhp\Converter\Configuration;
use Goetas\Xsd\XsdToPhp\Converter\Converter;
use Goetas\Xsd\XsdToPhp\Jms\PathGenerator\Psr4PathGenerator;
use Goetas\Xsd\XsdToPhp\Jms\YamlConverter;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertToYaml extends AbstractConvert
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('convert:jms-yaml');
        $this->setDescription('Convert XSD definitions into YAML metadata for JMS Serializer');
    }

    /**
     * @inheritdoc
     */
    protected function getConverter(Configuration $configuration)
    {
        return new YamlConverter($configuration);
    }

    /**
     * @inheritdoc
     */
    protected function convert(Converter $converter, array $schemas, OutputInterface $output)
    {
        $items = $converter->convert($schemas);

        $dumper = new Dumper();
        $pathGenerator = new Psr4PathGenerator($converter->getConfiguration()->getNamespaceDestinations());

        /** @var \Symfony\Component\Console\Helper\ProgressHelper $progress */
        $progress = $this->getHelperSet()->get('progress');
        $progress->start($output, count($items));

        foreach ($items as $item) {
            $progress->advance(1, true);
            $output->write(" Item <info>" . key($item) . "</info>... ");

            $source = $dumper->dump($item, 10000);
            $output->write("created source... ");

            $path = $pathGenerator->getPath($item);
            $bytes = file_put_contents($path, $source);
            $output->writeln("saved source <comment>$bytes bytes</comment>.");
        }
        $progress->finish();
    }
}
