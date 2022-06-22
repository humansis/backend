<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Identity;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\ImportLoggerTrait;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\ImportQueueTransitions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class ItemSimilarityCheckerService
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

            if ($i % 200 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param ImportQueue $item
     */
    public function checkOne(ImportQueue $item): void
    {
        // similarity check missing, it will be implemented later
        $item->setSimilarityCheckedAt(new \DateTime());
        $this->importQueueStateMachine->apply($item, ImportQueueTransitions::TO_CREATE);

        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }

    /**
     * @param Import $import
     */
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
