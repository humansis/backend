<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use NewApiBundle\Enum\ImportQueueState;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckIntegrityCommand extends AbstractImportQueueCommand
{
    protected function configure()
    {
        $this
            ->setName('app:import:integrity')
            ->setDescription('Run integrity check on loaded queue')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $queue = $this->getQueue([ImportQueueState::NEW]);
        $output->writeln([
            "Integrity check",
            count($queue)." items in queue",
        ]);
    }

}
