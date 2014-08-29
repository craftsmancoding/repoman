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

class AssetsPublish extends Command
{
    protected function configure()
    {
        $this
            ->setName('assets:publish')
            ->setDescription('Copy package web assets to proper sub-dir inside of assets/components/')
            ->addArgument(
                'pkg_root_dir',
                InputArgument::REQUIRED,
                'Path to package root'
            )
            ->addOption(
                'delete',
                null,
                InputOption::VALUE_NONE,
                'Whether to delete files that are not in the source directory',
                false
            )
            //->setHelp(file_get_contents(dirname(dirname(dirname(dirname(__FILE__)))) . '/docs/assets_publish.txt'))
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pkg_root_dir = $input->getArgument('pkg_root_dir');

        $output->writeln('Assets Publish...'.$pkg_root_dir);
    }
}
/*EOF*/