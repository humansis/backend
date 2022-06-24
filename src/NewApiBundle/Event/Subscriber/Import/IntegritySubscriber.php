<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\Import;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use NewApiBundle\Component\Import\ImportInvalidFileService;
use NewApiBundle\Component\Import\IntegrityChecker;
use NewApiBundle\Component\Import\Message\ImportCheck;
use NewApiBundle\Component\Import\Message\ItemBatch;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\ImportTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class IntegritySubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IntegrityChecker
     */
    private $integrityChecker;

    /**
     * @var EntityRepository|ObjectRepository|ImportQueueRepository
     */
    private $queueRepository;

    /**
     * @var ImportInvalidFileService
     */
    private $importInvalidFileService;

    /** @var MessageBusInterface */
    private $messageBus;

    /**
     * @var int
     */
    private $batchSize;

    public function __construct(
        EntityManagerInterface   $entityManager,
        IntegrityChecker         $integrityChecker,
        ImportInvalidFileService $importInvalidFileService,
        int                      $batchSize,
        MessageBusInterface      $messageBus,
        ImportQueueRepository    $queueRepository
    ) {
        $this->entityManager = $entityManager;
        $this->integrityChecker = $integrityChecker;
        $this->queueRepository = $queueRepository;
        $this->importInvalidFileService = $importInvalidFileService;
        $this->batchSize = $batchSize;
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.entered.'.ImportState::INTEGRITY_CHECKING => ['fillQueue'],
            'workflow.import.guard.'.ImportTransitions::COMPLETE_INTEGRITY => [
                ['guardNothingLeft', -10],
                ['guardNoItemsFailed', 10],
                ['guardNotEmptyImport', 20]
            ],
            'workflow.import.guard.'.ImportTransitions::FAIL_INTEGRITY => [
                ['guardNothingLeft', 0],
                ['guardSomeItemsFailedOrEmptyQueue', 20]
            ],
            'workflow.import.guard.'.ImportTransitions::REDO_INTEGRITY => ['guardSomeItemsLeft'],
            // 'workflow.import.entered.'.ImportTransitions::CHECK_INTEGRITY => ['checkIntegrity'],
            'workflow.import.completed.'.ImportTransitions::REDO_INTEGRITY => ['checkIntegrity'],
            'workflow.import.entered.'.ImportTransitions::FAIL_INTEGRITY => ['generateFile'],
        ];
    }

    public function fillQueue(EnteredEvent $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();

        foreach ($this->queueRepository->findBy([
            'import' => $import,
            'state' => ImportQueueState::NEW,
        ]) as $item) {
            $this->messageBus->dispatch(ItemBatch::checkSingleItemIntegrity($item));
        }
        $this->messageBus->dispatch(ImportCheck::checkIntegrityComplete($import));
    }

    /**
     * @param CompletedEvent $enteredEvent
     */
    public function checkIntegrity(CompletedEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->integrityChecker->check($import, $this->batchSize);
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardNotEmptyImport(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        if ($this->integrityChecker->isImportWithoutContent($import)) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Integrity has empty queue.', '0'));
        }
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardNoItemsFailed(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        if ($this->integrityChecker->hasImportQueueInvalidItems($import)) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Integrity has invalid items.', '0'));
        }
    }

    /**
     * @param GuardEvent $guardEvent
     */
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
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Integrity check was not completed', '0'));
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

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardSomeItemsFailed(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        $allItemsAreValid = !$this->integrityChecker->hasImportQueueInvalidItems($import);

        if ($allItemsAreValid) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Integrity check has some items valid', '0'));
        }
    }

    /**
     * @param Event $event
     */
    public function generateFile(Event $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();
        $this->importInvalidFileService->generateFile($import);
    }
}
