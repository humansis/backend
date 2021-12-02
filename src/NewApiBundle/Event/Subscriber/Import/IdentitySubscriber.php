<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\IdentityChecker;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Workflow\ImportTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
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
     * @var int
     */
    private $batchSize;

    public function __construct(EntityManagerInterface $entityManager, IdentityChecker $identityChecker, int $batchSize)
    {
        $this->entityManager = $entityManager;
        $this->identityChecker = $identityChecker;
        $this->batchSize = $batchSize;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.guard.'.ImportTransitions::CHECK_IDENTITY => ['guardIfImportHasValidQueueItems'],
            'workflow.import.guard.'.ImportTransitions::REDO_IDENTITY => ['guardIfImportHasValidQueueItems'],
            'workflow.import.guard.'.ImportTransitions::COMPLETE_IDENTITY => ['guardIfImportIsNotSuspicious'],
            'workflow.import.guard.'.ImportTransitions::FAIL_IDENTITY => ['guardIfImportIsInvalid'],
            'workflow.import.guard.'.ImportTransitions::RESOLVE_IDENTITY_DUPLICITIES => ['guardIfImportIsNotSuspicious'],
            // 'workflow.import.entered.'.ImportTransitions::CHECK_IDENTITY => ['checkIdentity'],
            'workflow.import.completed.'.ImportTransitions::REDO_IDENTITY => ['checkIdentity'],
        ];
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardIfImportHasValidQueueItems(GuardEvent $guardEvent): void
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
    public function guardIfImportIsInvalid(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();
        $isSuspicious = $this->identityChecker->isImportQueueSuspicious($import);
        if ($isSuspicious === false) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Import is valid', '0'));
        }
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardIfImportIsNotSuspicious(GuardEvent $guardEvent): void
    {

        /** @var Import $import */
        $import = $guardEvent->getSubject();
        $isSuspicious = $this->identityChecker->isImportQueueSuspicious($import);
        if ($isSuspicious === true) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Import is suspicious', '0'));
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
