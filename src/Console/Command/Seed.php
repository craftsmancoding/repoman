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

class Seed extends Command
{
    protected function configure()
    {
        $this
            ->setName('schema:parse')
            ->setDescription("Add records to your database from seed files.")
            ->addArgument(
                'pkg_root_dir',
                InputArgument::REQUIRED,
                'Path to package root'
            )
            ->setHelp(file_get_contents(dirname(dirname(dirname(dirname(__FILE__)))) . '/docs/seed.txt'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pkg_root_dir = $input->getArgument('pkg_root_dir');

        $output->writeln('Seed '.$pkg_root_dir);
    }
}
/*EOF*/