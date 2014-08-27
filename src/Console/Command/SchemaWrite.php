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

class SchemaWrite extends Command
{
    protected function configure()
    {
        $this
            ->setName('schema:write')
            ->setDescription("Write an XML schema file based off of existing database tables, usually identified by their name prefix")
            ->addArgument(
                'pkg_root_dir',
                InputArgument::REQUIRED,
                'Path to package root'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pkg_root_dir = $input->getArgument('pkg_root_dir');

        $output->writeln('Schema Write '.$pkg_root_dir);
    }
}
/*EOF*/