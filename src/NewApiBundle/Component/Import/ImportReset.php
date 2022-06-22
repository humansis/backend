<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use http\Exception\BadMethodCallException;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\ImportQueueTransitions;
use NewApiBundle\Workflow\ImportTransitions;
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
     * @param Import $import
     */
    public function resetOtherImports(Import $import)
    {
        if ($import->getState() !== ImportState::FINISHED) {
            throw new BadMethodCallException('Wrong import status');
        }

        $importConflicts = $this->em->getRepository(Import::class)->getConflictingImports($import);
        $this->logImportInfo($import, count($importConflicts)." conflicting imports to reset duplicity checks");
        foreach ($importConflicts as $conflictImport) {
            if ($this->importStateMachine->can($conflictImport, ImportTransitions::RESET)) {
                $this->logImportInfo($conflictImport, " reset to ".ImportState::IDENTITY_CHECKING);
                $this->importStateMachine->apply($conflictImport, ImportTransitions::RESET);
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
