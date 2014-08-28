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

class Build extends Command
{
    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Create MODX build package')
            ->addArgument(
                'pkg_root_dir',
                InputArgument::REQUIRED,
                'Path to package root'
            )
            ->addOption(
                'strip_docblocks',
                null,
                InputOption::VALUE_NONE,
                'Strip out the first block comment from elements, the first block comment is assumed to be the docblock.'
            )
            ->addOption(
                'strip_comments',
                null,
                InputOption::VALUE_NONE,
                'Strip out all comments and whitespace from elements, including docblocks.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pkg_root_dir = $input->getArgument('pkg_root_dir');

        $output->writeln('BUILD...'.$pkg_root_dir);
    }
}
/*EOF*/