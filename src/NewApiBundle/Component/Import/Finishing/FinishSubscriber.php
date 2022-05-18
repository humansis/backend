<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Finishing;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use NewApiBundle\Component\Import\ImportFinisher;
use NewApiBundle\Component\Import\ImportReset;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Component\Import\Workflow\ImportTransitions;
use NewApiBundle\Workflow\WorkflowTool;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class FinishSubscriber implements EventSubscriberInterface
{
    /**
     * @var ImportFinisher
     */
    private $importFinisher;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ImportReset
     */
    private $importReset;

    public function __construct(EntityManagerInterface $entityManager, ImportFinisher $importFinisher, ImportReset $importReset)
    {
        $this->importFinisher = $importFinisher;
        $this->entityManager = $entityManager;
        $this->importReset = $importReset;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.guard.'.ImportTransitions::FINISH => ['guardAllItemsAreImported'],
            'workflow.import.guard.'.ImportTransitions::IMPORT => ['guardIfThereIsOnlyOneFinishingImport'],
            'workflow.import.completed.'.ImportTransitions::RESET => ['resetImport'],
        ];
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
    public function resetImport(CompletedEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->importReset->reset($import);
    }
}