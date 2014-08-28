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

class Uninstall extends Command
{
    protected function configure()
    {
        $this
            ->setName('uninstall')
            ->setDescription("Uninstall a modx-package.")
            ->addArgument(
                'pkg_root_dir',
                InputArgument::REQUIRED,
                'Path to package root'
            )
            ->setHelp(file_get_contents(dirname(dirname(dirname(dirname(__FILE__)))) . '/docs/uninstall.txt'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pkg_root_dir = $input->getArgument('pkg_root_dir');

        $output->writeln('Uninstall '.$pkg_root_dir);
    }
}
/*EOF*/