<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use Psr\Log\LoggerInterface;
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

        if (empty($this->imports)) {
            $this->imports = $this->manager->getRepository(Import::class)
                ->findBy([
                    'state' => ImportState::IMPORTING,
                ]);
        }

        if (!empty($this->imports)) {
            $this->logAffectedImports($this->imports, 'app:import:finish');
        } else {
            $this->logger->debug('app:import:finish affects no imports');
        }

        $output->writeln([
            "Finishing of ".count($this->imports)." imports",
        ]);

        /** @var Import $import */
        foreach ($this->imports as $import) {
            $output->writeln($import->getTitle());
            $this->importService->finish($import);

            $this->logImportDebug($import, "Finished");
        }
        $output->writeln('Imports finishing completed');
    }
}
