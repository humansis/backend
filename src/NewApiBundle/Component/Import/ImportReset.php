<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\ImportQueueTransitions;
use NewApiBundle\Workflow\WorkflowTool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class ImportReset
{
    use ImportLoggerTrait;
    use ImportQueueLoggerTrait;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var WorkflowInterface
     */
    private $importStateMachine;

    /**
     * @var WorkflowInterface
     */
    private $importQueueStateMachine;

    /**
     * @var ObjectRepository|ImportQueueRepository
     */
    private $queueRepository;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface        $logger,
        WorkflowInterface      $importStateMachine,
        WorkflowInterface      $importQueueStateMachine
    ) {
        $this->em = $em;
        $this->queueRepository = $this->em->getRepository(ImportQueue::class);
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
        $this->logger = $logger;
    }

    /**
     * @param Import $conflictImport
     */
    public function reset(Import $conflictImport): void
    {
        $conflictQueue = $this->queueRepository->findBy([
            'import' => $conflictImport,
        ]);
        foreach ($conflictQueue as $item) {
            $this->resetItem($item);
        }
        $this->em->flush();
        $this->logImportInfo($conflictImport,
            "Duplicity checks of ".count($conflictQueue)." queue items reset because finish Import#{$conflictImport->getId()} ({$conflictImport->getTitle()})");
    }

    /**
     * @param ImportQueue $item
     */
    private function resetItem(ImportQueue $item): void
    {
        $item->setIdentityCheckedAt(null);
        $item->setSimilarityCheckedAt(null);

        foreach ($item->getDuplicities() as $duplicity) {
            $this->em->remove($duplicity);
        }

        if ($this->importQueueStateMachine->can($item, ImportQueueTransitions::RESET)) {
            $this->importQueueStateMachine->apply($item, ImportQueueTransitions::RESET);
        } else {
            $this->logQueueTransitionConstraints($this->importQueueStateMachine, $item,ImportQueueTransitions::RESET);
        }

        $this->em->persist($item);
    }
}
