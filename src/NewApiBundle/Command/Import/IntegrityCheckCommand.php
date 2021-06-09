<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\ImportInvalidFileService;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Component\Import\IntegrityChecker;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class IntegrityCheckCommand extends AbstractImportQueueCommand
{

    public function __construct(ObjectManager $manager, ImportService $importService, LoggerInterface $importLogger)
    {
        parent::__construct($manager, $importService, $importLogger);
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('app:import:integrity')
            ->setDescription('Run integrity check on loaded queue')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if (empty($this->imports)) {
            $this->imports = $this->manager->getRepository(Import::class)
                ->findBy([
                    'state' => ImportState::INTEGRITY_CHECKING,
                ]);
        }

        if (!empty($this->imports)) {
            $this->logAffectedImports($this->imports, 'app:import:integrity');
        } else {
            $this->logger->debug('app:import:integrity affects no imports');
        }

        $output->write($this->getName()." checking integrity of ".count($this->imports)." imports ");

        /** @var Import $import */
        foreach ($this->imports as $import) {
            $output->writeln($import->getTitle());

            try {
                $this->importService->checkIntegrity($import);

                $statistics = $this->importService->getStatistics($import);
                if (ImportState::INTEGRITY_CHECK_CORRECT === $import->getState()) {
                    $this->logImportInfo($import, "Integrity check was successful: {$statistics->getAmountIntegrityCorrect()} correct records");
                } else {
                    $this->logImportInfo($import, "Integrity check found {$statistics->getAmountIntegrityFailed()} integrity errors");
                }
            } catch (Throwable $e) {
                $this->logImportWarning($import, 'Unknown Exception in integrity check occurred. Exception message: '.$e->getMessage()); //TODO Error
            }
        }

        $output->writeln('Done');
    }

}
