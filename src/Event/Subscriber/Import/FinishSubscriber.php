<?php

declare(strict_types=1);

namespace Event\Subscriber\Import;

use Doctrine\ORM\EntityManagerInterface;
use Component\Import\ImportReset;
use Component\Import\Messaging\Message\ImportCheck;
use Component\Import\Messaging\Message\ItemBatch;
use Entity\Import;
use Enum\ImportQueueState;
use Enum\ImportState;
use Repository\ImportQueueRepository;
use Repository\ImportRepository;
use Workflow\ImportTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;
use Symfony\Contracts\Translation\TranslatorInterface;

class FinishSubscriber implements EventSubscriberInterface
{
    public const GUARD_CODE_NOT_COMPLETE = '810bf93b-7e86-45a8-a694-ba15428b4703';

    /** @var ImportQueueRepository */
    private $queueRepository;

    /** @var ImportRepository */
    private $importRepository;

    /** @var MessageBusInterface */
    private $messageBus;

    /** @var ImportReset */
    private $importReset;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        ImportReset $importReset,
        ImportQueueRepository $queueRepository,
        MessageBusInterface $messageBus,
        ImportRepository $importRepository,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->importReset = $importReset;
        $this->queueRepository = $queueRepository;
        $this->messageBus = $messageBus;
        $this->importRepository = $importRepository;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.entered.' . ImportState::IMPORTING => ['fillQueue'],
            'workflow.import.guard.' . ImportTransitions::FINISH => ['guardAllItemsAreImported'],
            'workflow.import.guard.' . ImportTransitions::IMPORT => [
                ['guardIfThereIsOnlyOneFinishingImport', 0],
                ['guardAllItemsAreReadyForImport', 10],
            ],
            'workflow.import.completed.' . ImportTransitions::FINISH => ['resetOtherImports'],
        ];
    }

    public function fillQueue(EnteredEvent $event): void
    {
        /**
         * This is important because Import object is not yet flushed
         */
        $this->entityManager->flush();
        /** @var Import $import */
        $import = $event->getSubject();

        foreach (
            $this->queueRepository->findBy([
                'import' => $import,
                'state' => ImportQueueState::TO_CREATE,
            ]) as $item
        ) {
            $this->messageBus->dispatch(ItemBatch::finishSingleItem($item));
        }

        foreach (
            $this->queueRepository->findBy([
                'import' => $import,
                'state' => ImportQueueState::TO_UPDATE,
            ]) as $item
        ) {
            $this->messageBus->dispatch(ItemBatch::finishSingleItem($item));
        }

        foreach (
            $this->queueRepository->findBy([
                'import' => $import,
                'state' => ImportQueueState::TO_LINK,
            ]) as $item
        ) {
            $this->messageBus->dispatch(ItemBatch::finishSingleItem($item));
        }

        foreach (
            $this->queueRepository->findBy([
                'import' => $import,
                'state' => ImportQueueState::TO_IGNORE,
            ]) as $item
        ) {
            $this->messageBus->dispatch(ItemBatch::finishSingleItem($item));
        }

        $this->messageBus->dispatch(ImportCheck::checkImportingComplete($import), [new DelayStamp(5000)]);
    }

    public function guardAllItemsAreImported(GuardEvent $event)
    {
        /** @var Import $import */
        $import = $event->getSubject();

        $entriesReadyForImport = $this->queueRepository->getTotalReadyForSave($import);
        if ($entriesReadyForImport > 0) {
            $event->addTransitionBlocker(
                new TransitionBlocker(
                    'Import can\'t be finished because there are still ' . $entriesReadyForImport . ' entries ready for import',
                    self::GUARD_CODE_NOT_COMPLETE
                )
            );
        }
    }

    /**
     * @param GuardEvent $event
     */
    public function guardIfThereIsOnlyOneFinishingImport(GuardEvent $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();

        if (!$this->importRepository->isCountryFreeFromImporting($import, $import->getCountryIso3())) {
            $event->addTransitionBlocker(
                new TransitionBlocker(
                    $this->translator->trans(
                        'Unfortunately, another import is running now and this import cannot start. This import will be returned to Identity check, when the other import is completed. Then, you can finish this import.'
                    ),
                    '0'
                )
            );
        }
    }

    /**
     * @param CompletedEvent $enteredEvent
     */
    public function resetOtherImports(CompletedEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->importReset->resetOtherImports($import);
    }

    public function guardAllItemsAreReadyForImport(GuardEvent $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();

        $entryToSave = $this->queueRepository->getTotalReadyForSave($import);
        $entryToSave += $this->queueRepository->getTotalByImportAndStatus($import, ImportQueueState::INVALID_EXPORTED);

        if ($this->queueRepository->countByImport($import) != $entryToSave) {
            $event->addTransitionBlocker(
                new TransitionBlocker("One or more item of import #{$import->getId()} are not ready for import.", '0')
            );
        }
    }
}
