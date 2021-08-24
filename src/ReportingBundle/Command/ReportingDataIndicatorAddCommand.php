<?php

namespace ReportingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReportingDataIndicatorAddCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('reporting:data-indicator:add')

            // the short description shown while running "php bin/console list"
            ->setDescription('add data from dataFillers in reporting reference')

            // the full command description shown when running the command with
             // the "--help" option
             ->setHelp('add data from dataFillers in reporting reference')

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

        $em = $this->getContainer()->get('doctrine')->getManager();

        $indicators = $em->getRepository('ReportingBundle:ReportingIndicator')->findAll();

        foreach ($indicators as $indicator) {
            $this->getContainer()->get('reporting.data_fillers.default')->fill($indicator);
            $output->writeln($indicator->getCode());
        }

        
        $output->writeln([
            '============',
            'Data loaded',
        ]);
        return 0;
    }
}
