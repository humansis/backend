<?php

declare(strict_types=1);

namespace Event\Subscriber\Import;

use Doctrine\ORM\EntityManagerInterface;
use Component\Import\IdentityChecker;
use Component\Import\Messaging\Message\ImportCheck;
use Component\Import\Messaging\Message\ItemBatch;
use Entity\Import;
use Entity\ImportQueue;
use Enum\ImportQueueState;
use Enum\ImportState;
use Repository\ImportQueueRepository;
use Workflow\ImportTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class IdentitySubscriber implements EventSubscriberInterface
{
    final public const GUARD_CODE_NOT_COMPLETE = '99a555c7-6ab3-4fa8-9c42-705b4c70931c';

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly IdentityChecker $identityChecker, private readonly MessageBusInterface $messageBus, private readonly ImportQueueRepository $queueRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.entered.' . ImportState::IDENTITY_CHECKING => ['fillQueue'],
            'workflow.import.guard.' . ImportTransitions::COMPLETE_IDENTITY => [
                ['guardNothingLeft', -20],
                ['guardNoSuspiciousItem', -10],
                ['guardAllItemsChecked', 0],
            ],
            'workflow.import.guard.' . ImportTransitions::FAIL_IDENTITY => [
                ['guardNothingLeft', -10],
                ['guardAllItemsChecked', 0],
                ['guardAnySuspiciousItem', 10],
            ],
            'workflow.import.guard.' . ImportTransitions::RESOLVE_IDENTITY_DUPLICITIES => [
                ['guardAllItemsChecked', 0],
                ['guardNoSuspiciousItem', 10],
            ],
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
                'state' => ImportQueueState::VALID,
            ]) as $item
        ) {
            $this->messageBus->dispatch(ItemBatch::checkSingleItemIdentity($item));
        }

        $this->messageBus->dispatch(ImportCheck::checkIdentityComplete($import), [new DelayStamp(5000)]);
    }

    public function guardAllItemsChecked(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        if (
            0 < $this->entityManager->getRepository(ImportQueue::class)->count([
                'import' => $import,
                'state' => ImportQueueState::VALID,
            ])
        ) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('No valid queue items', '0'));
        }
    }

    public function guardAnySuspiciousItem(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();
        $isSuspicious = $this->identityChecker->isImportQueueSuspicious($import);
        if ($isSuspicious === false) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Import has no duplicity suspicious items', '0'));
        }
    }

    public function guardNoSuspiciousItem(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();
        // dont commit this
        $suspicious = $this->identityChecker->getSuspiciousItems($import);
        foreach ($suspicious as $susp) {
            $guardEvent->addTransitionBlocker(
                new TransitionBlocker('Import has duplicity suspicious item #' . $susp->getId(), '0')
            );
        }
    }

    public function guardNothingLeft(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        $isComplete = (0 === $this->queueRepository->countItemsToIdentityCheck($import));

        if (!$isComplete) {
            $guardEvent->addTransitionBlocker(
                new TransitionBlocker('Identity check was not completed', static::GUARD_CODE_NOT_COMPLETE)
            );
        }
    }
}
