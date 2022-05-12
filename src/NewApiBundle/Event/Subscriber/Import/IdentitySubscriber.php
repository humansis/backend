<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\Import;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use NewApiBundle\Component\Import\IdentityChecker;
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
use Symfony\Component\Workflow\Event\EnterEvent;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class IdentitySubscriber implements EventSubscriberInterface
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IdentityChecker
     */
    private $identityChecker;

    /**
     * @var EntityRepository|ObjectRepository|ImportQueueRepository
     */
    private $queueRepository;

    /** @var MessageBusInterface */
    private $messageBus;

    /**
     * @var int
     */
    private $batchSize;

    public function __construct(
        EntityManagerInterface $entityManager,
        IdentityChecker        $identityChecker,
        int                    $batchSize,
        MessageBusInterface    $messageBus
    ) {
        $this->entityManager = $entityManager;
        $this->identityChecker = $identityChecker;
        $this->batchSize = $batchSize;
        $this->messageBus = $messageBus;
        $this->queueRepository = $this->entityManager->getRepository(ImportQueue::class);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.entered.'.ImportTransitions::CHECK_IDENTITY => ['fillQueue'],
            'workflow.import.guard.'.ImportTransitions::REDO_IDENTITY => ['guardAnyValidItems'],
            'workflow.import.guard.'.ImportTransitions::COMPLETE_IDENTITY => ['guardNoSuspiciousItem'],
            'workflow.import.guard.'.ImportTransitions::FAIL_IDENTITY => ['guardAnySuspiciousItem'],
            'workflow.import.guard.'.ImportTransitions::RESOLVE_IDENTITY_DUPLICITIES => ['guardNoSuspiciousItem'],
            // 'workflow.import.entered.'.ImportTransitions::CHECK_IDENTITY => ['checkIdentity'],
            'workflow.import.completed.'.ImportTransitions::REDO_IDENTITY => ['checkIdentity'],
        ];
    }

    public function fillQueue(EnteredEvent $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();

        foreach ($this->queueRepository->findByImport($import) as $item) {
            $this->messageBus->dispatch(new ItemBatch(ImportState::IDENTITY_CHECKING, [$item->getId()]));
        }

        $this->messageBus->dispatch(new ImportCheck(ImportState::IDENTITY_CHECKING, $import->getId()));
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardAnyValidItems(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        if (0 === $this->entityManager->getRepository(ImportQueue::class)->count([
                'import' => $import,
                'state' => ImportQueueState::VALID,
            ])) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('No valid queue items', '0'));
        }
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardAnySuspiciousItem(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();
        $isSuspicious = $this->identityChecker->isImportQueueSuspicious($import);
        if ($isSuspicious === false) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Import has no duplicity suspicious items', '0'));
        }
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardNoSuspiciousItem(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();
        // dont commit this
        $suspicious = $this->identityChecker->getSuspiciousItems($import);
        foreach ($suspicious as $susp) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Import has duplicity suspicious item #'.$susp->getId(), '0'));
        }
    }

    /**
     * @param CompletedEvent $enteredEvent
     */
    public function checkIdentity(CompletedEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->identityChecker->check($import, $this->batchSize);
    }
}
