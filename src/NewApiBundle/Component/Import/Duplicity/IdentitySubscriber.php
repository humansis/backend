<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Duplicity;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Entity\Queue;
use NewApiBundle\Component\Import\Enum\QueueState;
use NewApiBundle\Component\Import\Enum\Transitions;
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
            // 'workflow.import.guard.'.Transitions::CHECK_IDENTITY => ['guardAnyValidItems'],
            'workflow.import.guard.'.Transitions::REDO_IDENTITY => ['guardAnyValidItems'],
            'workflow.import.guard.'.Transitions::COMPLETE_IDENTITY => ['guardNoSuspiciousItem'],
            'workflow.import.guard.'.Transitions::FAIL_IDENTITY => ['guardAnySuspiciousItem'],
            'workflow.import.guard.'.Transitions::RESOLVE_IDENTITY_DUPLICITIES => ['guardNoSuspiciousItem'],
            // 'workflow.import.entered.'.Transitions::CHECK_IDENTITY => ['checkIdentity'],
            'workflow.import.completed.'.Transitions::REDO_IDENTITY => ['checkIdentity'],
        ];
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardAnyValidItems(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        if (0 === $this->entityManager->getRepository(Queue::class)->count([
                'import' => $import,
                'state' => QueueState::VALID,
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
        $isSuspicious = $this->identityChecker->isQueueSuspicious($import);
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
