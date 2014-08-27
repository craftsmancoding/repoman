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

class Help extends Command
{
    protected function configure()
    {
        $this
            ->setName('help')
            ->setDescription("Get manual page for a given function.")
            ->addArgument(
                'function',
                InputArgument::OPTIONAL,
                'Name of function',
                'help'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $raw_function = $input->getArgument('function');

        $function = str_replace(':', '_', $raw_function);
        $doc = dirname(dirname(dirname(dirname(__FILE__)))) . '/docs/' . basename($function) . '.txt';
        if (file_exists($doc)) {
            $contents = file_get_contents($doc);
            $title = strtok($contents, "\n");
            $contents = preg_replace('/^.+\n/', '', $contents);
            $output->writeln('');
            $output->writeln('<bg=cyan> HELP: </bg=cyan> '.$title);
            $output->write( $contents, true );
            $output->writeln('');

        }
        else {
            $output->writeln('<fg=red>No manual page found for '.$raw_function.'</fg=red>');
        }

    }
}
/*EOF*/