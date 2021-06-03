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

class FindSimilarityDuplicityCommand extends AbstractImportQueueCommand
{
    /** @var SimilarityChecker */
    private $similarityChecker;

    public function __construct(ObjectManager $manager, ImportService $importService, LoggerInterface $importLogger,
                                SimilarityChecker $similarityChecker
    )
    {
        parent::__construct($manager, $importService, $importLogger);
        $this->similarityChecker = $similarityChecker;
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
                $this->similarityChecker->check($import);

                if (ImportState::SIMILARITY_CHECK_CORRECT === $import->getState()) {
                    $this->logImportDebug($import, "Similarity check found no duplicities");
                } else {
                    $statistics = $this->importService->getStatistics($import);
                    $this->logImportInfo($import, "Similarity check found {$statistics->getAmountDuplicities()} duplicities");
                }
            } catch (Throwable $e) {
                $this->logImportWarning($import, 'Unknown Exception in similarity check occurred. Exception message: '.$e->getMessage()); //TODO Error
            }

        }

        $this->manager->flush();

        $output->writeln('Done');
    }
}
