<?php

declare(strict_types=1);

namespace Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use http\Exception\BadMethodCallException;
use Entity\Import;
use Entity\ImportQueue;
use Enum\ImportState;
use Repository\ImportQueueRepository;
use Repository\ImportRepository;
use Workflow\ImportQueueTransitions;
use Workflow\ImportTransitions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class ImportReset
{
    use ImportLoggerTrait;
    use ImportQueueLoggerTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        LoggerInterface $logger,
        private readonly WorkflowInterface $importStateMachine,
        private readonly WorkflowInterface $importQueueStateMachine,
        private readonly ImportRepository $importRepository,
        private readonly ImportQueueRepository $queueRepository
    ) {
        $this->logger = $logger;
    }

    public function resetOtherImports(Import $import)
    {
        if ($import->getState() !== ImportState::FINISHED) {
            throw new BadMethodCallException(
                "Cannot reset import #{$import->getId()} which is at state {$import->getState()}. Only imports at state Finished are allowed."
            );
        }

        $importConflicts = $this->importRepository->getConflictingImports($import);
        $this->logImportInfo($import, count($importConflicts) . " conflicting imports to reset duplicity checks");
        foreach ($importConflicts as $conflictImport) {
            if ($this->importStateMachine->can($conflictImport, ImportTransitions::RESET)) {
                $this->logImportInfo($conflictImport, "reset to another duplicity check");
                $this->reset($conflictImport);
                $this->importStateMachine->apply($conflictImport, ImportTransitions::RESET);
            } else {
                $this->logImportTransitionConstraints(
                    $this->importStateMachine,
                    $conflictImport,
                    ImportTransitions::RESET
                );
            }
        }
        $this->em->flush();
    }

    public function reset(Import $conflictImport): void
    {
        $conflictQueue = $this->queueRepository->findBy([
            'import' => $conflictImport,
        ]);
        foreach ($conflictQueue as $item) {
            $this->resetItem($item);
        }
        $this->em->flush();
        $this->logImportInfo(
            $conflictImport,
            "Duplicity checks of " . count(
                $conflictQueue
            ) . " queue items reset because finish Import#{$conflictImport->getId()} ({$conflictImport->getTitle()})"
        );
    }

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
            $this->logQueueTransitionConstraints($this->importQueueStateMachine, $item, ImportQueueTransitions::RESET);
        }

        $this->em->persist($item);
    }
}
