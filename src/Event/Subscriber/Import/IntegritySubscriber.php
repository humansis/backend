<?php

declare(strict_types=1);

namespace Event\Subscriber\Import;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Component\Import\ImportInvalidFileService;
use Component\Import\IntegrityChecker;
use Component\Import\Messaging\Message\ImportCheck;
use Component\Import\Messaging\Message\ItemBatch;
use Entity\Import;
use Enum\ImportQueueState;
use Enum\ImportState;
use Repository\ImportQueueRepository;
use Workflow\ImportTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class IntegritySubscriber implements EventSubscriberInterface
{
    final public const GUARD_CODE_NOT_COMPLETE = '93226f1a-1b68-4ed4-bd78-1129d16d3333';

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly IntegrityChecker $integrityChecker, private readonly ImportInvalidFileService $importInvalidFileService, private readonly MessageBusInterface $messageBus, private readonly ImportQueueRepository $queueRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.entered.' . ImportState::INTEGRITY_CHECKING => ['checkIntegrity'],
            'workflow.import.guard.' . ImportTransitions::COMPLETE_INTEGRITY => [
                ['guardNothingLeft', -10],
                ['guardNoItemsFailed', 10],
                ['guardNotEmptyImport', 20],
            ],
            'workflow.import.guard.' . ImportTransitions::FAIL_INTEGRITY => [
                ['guardNothingLeft', 0],
                ['guardSomeItemsFailedOrEmptyQueue', 20],
            ],
            'workflow.import.guard.' . ImportTransitions::REDO_INTEGRITY => ['guardSomeItemsLeft'],
            'workflow.import.completed.' . ImportTransitions::REDO_INTEGRITY => ['checkIntegrityAgain'],
            'workflow.import.entered.' . ImportTransitions::FAIL_INTEGRITY => ['generateFile'],
        ];
    }

    public function checkIntegrity(EnteredEvent $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();
        $this->fillQueue($import);
    }

    public function checkIntegrityAgain(CompletedEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->fillQueue($import);
    }

    private function fillQueue(Import $import)
    {
        /**
         * This is important because Import object is not yet flushed
         */
        $this->entityManager->flush();
        foreach (
            $this->queueRepository->findBy([
                'import' => $import,
                'state' => ImportQueueState::NEW,
            ]) as $item
        ) {
            $this->messageBus->dispatch(ItemBatch::checkSingleItemIntegrity($item));
        }
        $this->messageBus->dispatch(ImportCheck::checkIntegrityComplete($import), [new DelayStamp(5000)]);
    }

    public function guardNotEmptyImport(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        if ($this->integrityChecker->isImportWithoutContent($import)) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Integrity has empty queue.', '0'));
        }
    }

    public function guardNoItemsFailed(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        if ($this->integrityChecker->hasImportQueueInvalidItems($import)) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Integrity has invalid items.', '0'));
        }
    }

    public function guardSomeItemsFailedOrEmptyQueue(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        $allValids = !$this->integrityChecker->hasImportQueueInvalidItems($import);
        $emptyImport = $this->integrityChecker->isImportWithoutContent($import);

        if ($allValids && !$emptyImport) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Integrity has all items valid.', '0'));
        }
    }

    public function guardNothingLeft(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        $isComplete = (0 === $this->queueRepository->countItemsToIntegrityCheck($import));

        if (!$isComplete) {
            $guardEvent->addTransitionBlocker(
                new TransitionBlocker('Integrity check was not completed', static::GUARD_CODE_NOT_COMPLETE)
            );
        }
    }

    public function guardSomeItemsLeft(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        $isComplete = (0 === $this->queueRepository->countItemsToIntegrityCheck($import));

        if ($isComplete) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Integrity check was completed', '0'));
        }
    }

    public function guardSomeItemsFailed(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        $allItemsAreValid = !$this->integrityChecker->hasImportQueueInvalidItems($import);

        if ($allItemsAreValid) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Integrity check has some items valid', '0'));
        }
    }

    public function generateFile(Event $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();
        $this->importInvalidFileService->generateFile($import);
    }
}
