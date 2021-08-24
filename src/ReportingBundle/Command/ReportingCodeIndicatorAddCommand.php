<?php

namespace ReportingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReportingCodeIndicatorAddCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('reporting:code-indicator:add')

            // the short description shown while running "php bin/console list"
            ->setDescription('add new code indicator in reporting reference')

            // the full command description shown when running the command with
             // the "--help" option
            ->setHelp('Add code indicator in reporting reference')

            ->addArgument('argument', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Execute script add code indicator',
            '============',
        ]);

        $this->getContainer()->get('reporting.data_fillers.reference')->fillIndicator();
            
        $output->writeln([
            '============',
            'Data loaded',
        ]);
        return 0;
    }
}
