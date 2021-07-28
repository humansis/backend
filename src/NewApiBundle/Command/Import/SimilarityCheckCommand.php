<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Component\Import\SimilarityChecker;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class SimilarityCheckCommand extends AbstractImportQueueCommand
{
    public function __construct(ObjectManager $manager, ImportService $importService, LoggerInterface $importLogger)
    {
        parent::__construct($manager, $importService, $importLogger);
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('app:import:similarity')
            ->setDescription('Run similarity duplicity check on import')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if (is_null($this->imports)) {
            $imports = [$this->imports];
        } else {
            $imports = $this->manager->getRepository(Import::class)
                ->findBy([
                    'state' => ImportState::SIMILARITY_CHECKING,
                ]);
        }

        if (!empty($this->imports)) {
            $this->logAffectedImports($this->imports, 'app:import:similarity');
        } else {
            $this->logger->debug('app:import:similarity affects no imports');
        }

        $output->write($this->getName()." finding duplicities in ".count($this->imports)." imports ");

        /** @var Import $import */
        foreach ($imports as $import) {
            try {
                $this->importService->checkSimilarity($import, $this->batchSize);

                if (ImportState::SIMILARITY_CHECK_CORRECT === $import->getState()) {
                    $this->logImportDebug($import, "Similarity check found no duplicities");
                } else {
                    $statistics = $this->importService->getStatistics($import);
                    $this->logImportInfo($import, "Similarity check found {$statistics->getAmountDuplicities()} duplicities");
                }
            } catch (Throwable $e) {
                $this->logImportError($import, 'Unknown Exception in similarity check occurred. Exception message: '.$e->getMessage());
            }

        }

        $this->manager->flush();

        $output->writeln('Done');
    }
}
