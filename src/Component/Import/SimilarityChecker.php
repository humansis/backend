<?php

declare(strict_types=1);

namespace Component\Import;

use BadMethodCallException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Entity\Import;
use Entity\ImportQueue;
use Enum\ImportQueueState;
use Enum\ImportState;
use Repository\ImportQueueRepository;
use Workflow\ImportQueueTransitions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class SimilarityChecker
{
    use ImportLoggerTrait;

    private readonly \Repository\ImportQueueRepository $queueRepository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        private readonly WorkflowInterface $importStateMachine,
        private readonly WorkflowInterface $importQueueStateMachine
    ) {
        $this->logger = $logger;
        $this->queueRepository = $this->entityManager->getRepository(ImportQueue::class);
    }

    /**
     * @param int|null $batchSize if null => all
     */
    public function check(Import $import, ?int $batchSize = null)
    {
        if (ImportState::SIMILARITY_CHECKING !== $import->getState()) {
            throw new BadMethodCallException('Unable to execute checker. Import is not ready to check.');
        }

        foreach ($this->queueRepository->getItemsToSimilarityCheck($import, $batchSize) as $i => $item) {
            $this->checkOne($item);

            if ($i % 200 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
    }

    public function checkOne(ImportQueue $item): void
    {
        // similarity check missing, it will be implemented later
        $item->setSimilarityCheckedAt(new DateTime());
        $this->importQueueStateMachine->apply($item, ImportQueueTransitions::TO_CREATE);

        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }

    public function postCheck(Import $import): void
    {
        // $newCheckedImportQueues = $this->entityManager->getRepository(ImportQueue::class)
        //     ->findBy([
        //         'import' => $import,
        //         'state' => ImportQueueState::VALID,
        //     ]);
        //
        // /** @var ImportQueue $importQueue */
        // foreach ($newCheckedImportQueues as $importQueue) {
        //     WorkflowTool::checkAndApply($this->importQueueStateMachine, $importQueue, [ImportQueueTransitions::TO_CREATE]);
        // }
        //
        // $this->entityManager->flush();
        // $this->logImportDebug($import, "Ended with status ".$import->getState());
    }

    public function isImportQueueSuspicious(Import $import): bool
    {
        $queue = $this->entityManager->getRepository(ImportQueue::class)
            ->findBy(['import' => $import, 'state' => ImportQueueState::SIMILARITY_CANDIDATE, 'decidedAt' => null]);

        return count($queue) > 0;
    }
}
