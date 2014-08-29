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
                'model',
                InputArgument::REQUIRED,
                'Name of model. This is is used as the basename of your schema file and it defines a subfolder inside your package\'s model/ directory.'
            )
            ->addArgument(
                'pkg_root_dir',
                InputArgument::REQUIRED,
                'Path to package root'
            )
            ->addOption(
                'table_prefix',
                null,
                InputOption::VALUE_REQUIRED,
                'Table prefix.  All tables whose names begin with this will be mapped in the schema.',
                false
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Force overwrite of existing files and directories.'
            )
            ->addOption(
                'polite',
                null,
                InputOption::VALUE_NONE,
                'Renames existing files and directories. More polite than the force option.'
            )
            ->addOption(
                'restrict_prefix',
                null,
                InputOption::VALUE_OPTIONAL,
                '.',
                true // default
            )

            ->setHelp(file_get_contents(dirname(dirname(dirname(dirname(__FILE__)))) . '/docs/schema_write.txt'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pkg_root_dir = $input->getArgument('pkg_root_dir');

        $output->writeln('Schema Write '.$pkg_root_dir);
    }
}
/*EOF*/