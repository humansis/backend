<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\Exception\WorkflowException;
use NewApiBundle\Workflow\ImportQueueTransitions;
use NewApiBundle\Workflow\ImportTransitions;
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
            $this->postCheck($import);
        } else {
            $this->logImportInfo($import, "Batch ended - $queueSize items left, similarity checking continues");
        }
    }

    protected function checkOne(ImportQueue $item)
    {
        // TODO: similarity check

        $item->setSimilarityCheckedAt(new \DateTime());

        $this->entityManager->persist($item);
    }

    private function postCheck(Import $import)
    {
        $isSuspicious = count($this->queueRepository->getSuspiciousItemsToUserCheck($import)) > 0;
        $transition = $isSuspicious ? ImportTransitions::FAIL_SIMILARITY : ImportTransitions::COMPLETE_SIMILARITY;

        if ($this->importStateMachine->can($import, $transition) === false) {
            $this->importStateMachine->apply($import, $transition);
            $import->setState($isSuspicious ? ImportState::SIMILARITY_CHECK_FAILED : ImportState::SIMILARITY_CHECK_CORRECT);
        }

        $newCheckedImportQueues = $this->entityManager->getRepository(ImportQueue::class)
            ->findBy([
                'import' => $import,
                'state' => ImportQueueState::VALID,
            ]);

        /** @var ImportQueue $importQueue */
        foreach ($newCheckedImportQueues as $importQueue) {
            $queueTransition = ImportQueueTransitions::TO_CREATE;
            if ($this->importQueueStateMachine->can($importQueue, $queueTransition)) {
                $this->importQueueStateMachine->apply($importQueue, $queueTransition);
                $importQueue->setState(ImportQueueState::TO_CREATE);
                $this->entityManager->persist($importQueue);
            } else {
                throw new WorkflowException('Import Queue is not in valid state.');
            }
        }

        $this->entityManager->persist($import);
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
            ->findBy(['import' => $import, 'state' => ImportQueueState::SUSPICIOUS, 'decidedAt' => null]);

        return count($queue) > 0;
    }
}
