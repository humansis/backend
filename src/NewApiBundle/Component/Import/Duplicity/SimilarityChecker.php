<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Duplicity;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Entity\Queue;
use NewApiBundle\Component\Import\Enum\QueueState;
use NewApiBundle\Component\Import\Enum\State;
use NewApiBundle\Component\Import\Utils\ImportLoggerTrait;
use NewApiBundle\Component\Import\Repository\QueueRepository;
use NewApiBundle\Component\Import\Enum\QueueTransitions;
use NewApiBundle\Component\Import\Enum\Transitions;
use NewApiBundle\Workflow\WorkflowTool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class SimilarityChecker
{
    use ImportLoggerTrait;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var QueueRepository */
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
        $this->queueRepository = $this->entityManager->getRepository(Queue::class);
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     */
    public function check(Import $import, ?int $batchSize = null)
    {
        if (State::SIMILARITY_CHECKING !== $import->getState()) {
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
                [Transitions::COMPLETE_SIMILARITY, Transitions::FAIL_SIMILARITY]);
            $this->entityManager->flush();
        } else {
            $this->logImportInfo($import, "Batch ended - $queueSize items left, similarity checking continues");
        }
    }

    /**
     * @param Queue $item
     */
    protected function checkOne(Queue $item): void
    {
        // TODO: similarity check
        $item->setSimilarityCheckedAt(new \DateTime());
        $this->entityManager->persist($item);

        WorkflowTool::checkAndApply($this->importQueueStateMachine, $item, [QueueTransitions::TO_CREATE]);
        $this->entityManager->flush();
    }

    /**
     * @param Import $import
     */
    public function postCheck(Import $import): void
    {
        $newCheckedQueues = $this->entityManager->getRepository(Queue::class)
            ->findBy([
                'import' => $import,
                'state' => QueueState::VALID,
            ]);

        /** @var Queue $importQueue */
        foreach ($newCheckedQueues as $importQueue) {
            WorkflowTool::checkAndApply($this->importQueueStateMachine, $importQueue, [QueueTransitions::TO_CREATE]);
        }

        $this->entityManager->flush();
        $this->logImportDebug($import, "Ended with status ".$import->getState());
    }

    /**
     * @param Import $import
     *
     * @return bool
     */
    public function isQueueSuspicious(Import $import): bool
    {
        $queue = $this->entityManager->getRepository(Queue::class)
            ->findBy(['import' => $import, 'state' => QueueState::SIMILARITY_CANDIDATE, 'decidedAt' => null]);

        return count($queue) > 0;
    }
}
