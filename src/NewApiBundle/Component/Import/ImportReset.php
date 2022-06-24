<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use http\Exception\BadMethodCallException;
use NewApiBundle\Component\Import\Message\ItemBatch;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Repository\ImportRepository;
use NewApiBundle\Workflow\ImportQueueTransitions;
use NewApiBundle\Workflow\ImportTransitions;
use NewApiBundle\Workflow\WorkflowTool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
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
     * @var ImportQueueRepository
     */
    private $queueRepository;

    /**
     * @var ImportRepository
     */
    private $importRepository;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface        $logger,
        WorkflowInterface      $importStateMachine,
        WorkflowInterface      $importQueueStateMachine,
        ImportRepository       $importRepository,
        ImportQueueRepository  $queueRepository
    ) {
        $this->em = $em;
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
        $this->logger = $logger;
        $this->importRepository = $importRepository;
        $this->queueRepository = $queueRepository;
    }

    /**
     * @param Import $import
     */
    public function resetOtherImports(Import $import)
    {
        if ($import->getState() !== ImportState::FINISHED) {
            throw new BadMethodCallException('Wrong import status');
        }

        $importConflicts = $this->importRepository->getConflictingImports($import);
        $this->logImportInfo($import, count($importConflicts)." conflicting imports to reset duplicity checks");
        foreach ($importConflicts as $conflictImport) {
            if ($this->importStateMachine->can($conflictImport, ImportTransitions::RESET)) {
                $this->logImportInfo($conflictImport, "reset to another duplicity check");
                $this->importStateMachine->apply($conflictImport, ImportTransitions::RESET);
                $this->reset($conflictImport);
            } else {
                $this->logImportTransitionConstraints($this->importStateMachine, $conflictImport, ImportTransitions::RESET);
            }

        }
        $this->em->flush();
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

        foreach ($item->getHouseholdDuplicities() as $duplicity) {
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
