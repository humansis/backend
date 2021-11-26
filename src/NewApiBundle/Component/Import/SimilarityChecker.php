<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\ImportQueueTransitions;
use NewApiBundle\Workflow\ImportTransitions;
use NewApiBundle\Workflow\WorkflowTool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class SimilarityChecker
{
    use ImportLoggerTrait;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ImportQueueRepository */
    private $queueRepository;

    /** @var WorkflowInterface */
    private $importStateMachine;

    /** @var WorkflowInterface */
    private $importQueueStateMachine;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface        $logger,
        WorkflowInterface      $importStateMachine,
        WorkflowInterface      $importQueueStateMachine
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->queueRepository = $this->entityManager->getRepository(ImportQueue::class);
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     */
    public function check(Import $import, ?int $batchSize = null)
    {
        if (ImportState::SIMILARITY_CHECKING !== $import->getState()) {
            throw new \BadMethodCallException('Unable to execute checker. Import is not ready to check.');
        }

        foreach ($this->queueRepository->getItemsToSimilarityCheck($import, $batchSize) as $i => $item) {
            $this->checkOne($item);

            if ($i % 500 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();

        $queueSize = $this->queueRepository->countItemsToSimilarityCheck($import);
        if (0 === $queueSize) {
            $this->logImportInfo($import, 'Batch ended - nothing left, similarity checking ends');
            WorkflowTool::checkAndApply($this->importStateMachine, $import,
                [ImportTransitions::COMPLETE_SIMILARITY, ImportTransitions::FAIL_SIMILARITY]);
        } else {
            $this->logImportInfo($import, "Batch ended - $queueSize items left, similarity checking continues");
        }
    }

    /**
     * @param ImportQueue $item
     */
    protected function checkOne(ImportQueue $item): void
    {
        // TODO: similarity check
        $item->setSimilarityCheckedAt(new \DateTime());
        $this->entityManager->persist($item);

        WorkflowTool::checkAndApply($this->importQueueStateMachine, $item, [ImportQueueTransitions::TO_CREATE]);
    }

    /**
     * @param Import $import
     */
    public function postCheck(Import $import): void
    {
        $newCheckedImportQueues = $this->entityManager->getRepository(ImportQueue::class)
            ->findBy([
                'import' => $import,
                'state' => ImportQueueState::VALID,
            ]);

        /** @var ImportQueue $importQueue */
        foreach ($newCheckedImportQueues as $importQueue) {
            WorkflowTool::checkAndApply($this->importQueueStateMachine, $importQueue, [ImportQueueTransitions::TO_CREATE]);
        }

        $this->entityManager->flush();
        $this->logImportDebug($import, "Ended with status ".$import->getState());
    }

    /**
     * @param Import $import
     *
     * @return bool
     */
    public function isImportQueueSuspicious(Import $import): bool
    {
        $queue = $this->entityManager->getRepository(ImportQueue::class)
            ->findBy(['import' => $import, 'state' => ImportQueueState::SIMILARITY_CANDIDATE, 'decidedAt' => null]);

        return count($queue) > 0;
    }
}
