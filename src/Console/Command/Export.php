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
                'Specific destination directory, or path to repo root to store data in recommended location within repo'
            )
            ->addOption(
                'where',
                null,
                InputOption::VALUE_REQUIRED,
                'JSON where clause filtering results from <classname> collection',
                null
            )
            ->addOption(
                'graph',
                null,
                InputOption::VALUE_REQUIRED,
                'JSON graph to define joins on related data',
                null
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'How many records per file?',
                1
            )
            ->addOption(
                'overwrite',
                null,
                InputOption::VALUE_NONE,
                'Use to overwrite existing files in the target directory'
            )
            ->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'Print raw SQL query and other debugging data.'
            )
            ->addOption(
                'dir',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                "Package directories where extra.packages are defined for MODX::addPackage(). Use this if your models are not listed in the extension_packages System Setting for automatic loading",
                array()
            )
            ->setHelp(file_get_contents(dirname(dirname(dirname(dirname(__FILE__)))) . '/docs/export.txt'));

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pkg_root_dir = $input->getArgument('pkg_root_dir');

        $output->writeln('Export...'.$pkg_root_dir);
    }
}
/*EOF*/