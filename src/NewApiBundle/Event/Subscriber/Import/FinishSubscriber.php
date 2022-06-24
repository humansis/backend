<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\ImportReset;
use NewApiBundle\Component\Import\Message\ImportCheck;
use NewApiBundle\Component\Import\Message\ItemBatch;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\ImportTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class FinishSubscriber implements EventSubscriberInterface
{
    /**
     * @var \NewApiBundle\Component\Import\ImportFinisher
     */
    private $importFinisher;

    /**
     * @var ImportQueueRepository
     */
    private $queueRepository;

    /** @var MessageBusInterface */
    private $messageBus;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ImportReset
     */
    private $importReset;

    public function __construct(
        EntityManagerInterface                        $entityManager,
        \NewApiBundle\Component\Import\ImportFinisher $importFinisher,
        ImportReset                                   $importReset,
        ImportQueueRepository                         $queueRepository,
        MessageBusInterface                           $messageBus
    ) {
        $this->importFinisher = $importFinisher;
        $this->entityManager = $entityManager;
        $this->importReset = $importReset;
        $this->queueRepository = $queueRepository;
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.entered.'.ImportState::IMPORTING => ['fillQueue'],
            'workflow.import.guard.'.ImportTransitions::FINISH => ['guardAllItemsAreImported'],
            'workflow.import.guard.'.ImportTransitions::IMPORT => ['guardIfThereIsOnlyOneFinishingImport', 'guardAllItemsAreReadyForImport'],
            'workflow.import.completed.'.ImportTransitions::FINISH => ['resetOtherImports'],
        ];
    }

    public function fillQueue(EnteredEvent $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();

        foreach ($this->queueRepository->findBy([
            'import' => $import,
            'state' => ImportQueueState::TO_CREATE,
        ]) as $item) {
            $this->messageBus->dispatch(ItemBatch::finishSingleItem($item));
        }

        foreach ($this->queueRepository->findBy([
            'import' => $import,
            'state' => ImportQueueState::TO_UPDATE,
        ]) as $item) {
            $this->messageBus->dispatch(ItemBatch::finishSingleItem($item));
        }

        foreach ($this->queueRepository->findBy([
            'import' => $import,
            'state' => ImportQueueState::TO_LINK,
        ]) as $item) {
            $this->messageBus->dispatch(ItemBatch::finishSingleItem($item));
        }

        foreach ($this->queueRepository->findBy([
            'import' => $import,
            'state' => ImportQueueState::TO_IGNORE,
        ]) as $item) {
            $this->messageBus->dispatch(ItemBatch::finishSingleItem($item));
        }

        $this->messageBus->dispatch(ImportCheck::checkImportingComplete($import));
    }

    public function guardAllItemsAreImported(GuardEvent $event)
    {
        /** @var Import $import */
        $import = $event->getSubject();

        /** @var ImportQueueRepository $importQueueRepository */
        $importQueueRepository = $this->entityManager->getRepository(ImportQueue::class);

        $entriesReadyForImport = $importQueueRepository->getTotalReadyForSave($import);
        if ($entriesReadyForImport > 0) {
            $event->addTransitionBlocker(new TransitionBlocker('Import can\'t be finished because there are still ' . $entriesReadyForImport . ' entries ready for import', '0'));
        }
    }

    /**
     * @param GuardEvent $event
     */
    public function guardIfThereIsOnlyOneFinishingImport(GuardEvent $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();

        if (!$this->entityManager->getRepository(Import::class)
            ->isCountryFreeFromImporting($import, $import->getCountryIso3())) {
            $event->addTransitionBlocker(new TransitionBlocker('There can be only one finishing import in country in single time.', '0'));
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

        /** @var ImportQueueRepository $importQueueRepository */
        $importQueueRepository = $this->entityManager->getRepository(ImportQueue::class);

        if ($importQueueRepository->countByImport($import) != $importQueueRepository->getTotalReadyForSave($import)) {
            $event->addTransitionBlocker(new TransitionBlocker("One or more item of import #{$import->getId()} are not ready for import.", '0'));
        }
    }
}
