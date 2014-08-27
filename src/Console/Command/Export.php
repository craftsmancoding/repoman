<?php
/**
 *
 */
namespace Repoman\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Export extends Command
{
    protected function configure()
    {
        $this
            ->setName('export')
            ->setDescription('Export MODX elements and objects from the MODX database into your repository as files.')
            ->addArgument(
                'classname',
                InputArgument::REQUIRED,
                'MODX Classname'
            )
            ->addArgument(
                'target',
                InputArgument::REQUIRED,
                'Destination directory'
            )
            ->addOption(
                'where',
                null,
                InputOption::VALUE_OPTIONAL,
                'JSON where clause filtering results from <classname> collection',
                null
            )
            ->addOption(
                'graph',
                null,
                InputOption::VALUE_OPTIONAL,
                'JSON graph to define joins on related data',
                null
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'How many records per file?',
                1
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pkg_root_dir = $input->getArgument('pkg_root_dir');

        $output->writeln('Export...'.$pkg_root_dir);
    }
}
/*EOF*/