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

class Graph extends Command
{
    protected function configure()
    {
        $this
            ->setName('export')
            ->setDescription("Prints all of an object's attributes and it includes meta data for each defined relation.")
            ->addArgument(
                'pkg_root_dir',
                InputArgument::REQUIRED,
                'Path to package root'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pkg_root_dir = $input->getArgument('pkg_root_dir');

        $output->writeln('Graph...'.$pkg_root_dir);
    }
}
/*EOF*/