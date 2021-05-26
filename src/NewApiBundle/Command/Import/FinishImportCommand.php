<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use NewApiBundle\Enum\ImportQueueState;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FinishImportCommand extends AbstractImportQueueCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('app:import:finish')
            ->setDescription('Save finished imports into DB')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $queue = $this->getQueue([ImportQueueState::VALID]);
        $output->writeln([
            "Finishing(saving) import into DB",
            count($queue)." items in queue",
        ]);
    }
}
