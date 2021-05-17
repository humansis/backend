<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use NewApiBundle\Enum\ImportQueueState;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindDuplicityCommand extends AbstractImportQueueCommand
{
    protected function configure()
    {
        $this
            ->setName('app:import:duplicity')
            ->setDescription('Identify duplicities in import')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $queue = $this->getQueue([ImportQueueState::VALID]);
        $output->writeln([
            "Duplicity check",
            count($queue)." items in queue",
        ]);
    }
}
