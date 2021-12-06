<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use NewApiBundle\Component\Import\Service\InvalidFileService;
use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Entity\Queue;
use NewApiBundle\Repository\QueueRepository;
use NewApiBundle\Component\Import\Enum\Transitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
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
     * @var EntityRepository|ObjectRepository|QueueRepository
     */
    private $queueRepository;

    /**
     * @var InvalidFileService
     */
    private $importInvalidFileService;

    /**
     * @var int
     */
    private $batchSize;

    public function __construct(
        EntityManagerInterface   $entityManager,
        IntegrityChecker         $integrityChecker,
        InvalidFileService $importInvalidFileService,
        int                      $batchSize
    ) {
        $this->entityManager = $entityManager;
        $this->integrityChecker = $integrityChecker;
        $this->queueRepository = $this->entityManager->getRepository(Queue::class);
        $this->importInvalidFileService = $importInvalidFileService;
        $this->batchSize = $batchSize;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.guard.'.Transitions::COMPLETE_INTEGRITY => [
                ['guardNothingLeft', -10],
                ['guardNoItemsFailed', 10],
                ['guardNotEmptyImport', 20]
            ],
            'workflow.import.guard.'.Transitions::FAIL_INTEGRITY => [
                ['guardNothingLeft', 0],
                ['guardSomeItemsFailedOrEmptyQueue', 20]
            ],
            'workflow.import.guard.'.Transitions::REDO_INTEGRITY => ['guardSomeItemsLeft'],
            // 'workflow.import.entered.'.Transitions::CHECK_INTEGRITY => ['checkIntegrity'],
            'workflow.import.completed.'.Transitions::REDO_INTEGRITY => ['checkIntegrity'],
            'workflow.import.entered.'.Transitions::FAIL_INTEGRITY => ['generateFile'],
        ];
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

        if ($this->integrityChecker->hasQueueInvalidItems($import)) {
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

        $allValids = !$this->integrityChecker->hasQueueInvalidItems($import);
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

        $allItemsAreValid = !$this->integrityChecker->hasQueueInvalidItems($import);

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
