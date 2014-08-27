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

class Import extends Command
{
    protected function configure()
    {
        $this
            ->setName('import')
            ->setDescription("Import elements from the filesystem into MODX, including templates, chunks, and snippets.")
            ->addArgument(
                'pkg_root_dir',
                InputArgument::REQUIRED,
                'Path to package root'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pkg_root_dir = $input->getArgument('pkg_root_dir');

        $output->writeln('Import...'.$pkg_root_dir);
    }
}
/*EOF*/