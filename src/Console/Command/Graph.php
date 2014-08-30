<?php
/**
 *
 */
namespace Repoman\Console\Command;

use Repoman\Utils;
//use Repoman\Action\Graph;
use Repoman\Config;
use Repoman\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class Graph extends Command
{


    protected function configure()
    {
        $this
            ->setName('graph')
            ->setDescription("Prints all of an object's attributes its relations.")
            ->addArgument(
                'classname',
                InputArgument::OPTIONAL,
                'Path to package root'
            )
            ->addOption(
                'load',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                "One or more directories where extra.packages are defined in composer.json as arguments for MODX::addPackage(). Use this if your models are not listed in the extension_packages System Setting for automatic loading",
                array()
            )
            ->addOption(
                'aggregates',
                'a',
                InputOption::VALUE_NONE,
                'Show only related classes which are aggregates. If the primary object is deleted, aggregate objects are not affected.'
            )
            ->addOption(
                'composites',
                'c',
                InputOption::VALUE_NONE,
                'Show only related classes which are composites. If the primary object is deleted, composite objects are also deleted.'
            )
            ->setHelp(file_get_contents(dirname(dirname(dirname(dirname(__FILE__)))) . '/docs/graph.txt'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $graph = new \Repoman\Action\Graph(Utils::getMODX(), new Config(Utils::getMODX(), new Filesystem()));
        $classname = $input->getArgument('classname');
        $options = $input->getOptions();
        $out = $graph->execute($classname,$options);
        $output->write($out);
        //$output->writeln('Graph...'.$pkg_root_dir);
    }
}
/*EOF*/