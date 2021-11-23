<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\IdentityChecker;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Workflow\ImportTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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

    public function __construct(EntityManagerInterface $entityManager, IdentityChecker $identityChecker)
    {
        $this->entityManager = $entityManager;
        $this->identityChecker = $identityChecker;
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardCheckIdentity(GuardEvent $guardEvent): void
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
    public function guardFailIdentity(GuardEvent $guardEvent): void
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
    public function guardCompleteIdentity(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();
        $isSuspicious = $this->identityChecker->isImportQueueSuspicious($import);
        if ($isSuspicious === true) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Import is suspicious', '0'));
        }
    }

    /**
     * @param EnteredEvent $enteredEvent
     */
    public function enteredIdentity(EnteredEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->identityChecker->check($import);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.guard.'.ImportTransitions::CHECK_IDENTITY => ['guardCheckIdentity'],
            'workflow.import.guard.'.ImportTransitions::REDO_IDENTITY => ['guardCheckIdentity'],
            'workflow.import.guard.'.ImportTransitions::COMPLETE_IDENTITY => ['guardCompleteIdentity'],
            'workflow.import.guard.'.ImportTransitions::FAIL_IDENTITY => ['guardFailIdentity'],
            'workflow.import.entered.'.ImportTransitions::CHECK_IDENTITY => ['enteredIdentity'],
        ];
    }
}
