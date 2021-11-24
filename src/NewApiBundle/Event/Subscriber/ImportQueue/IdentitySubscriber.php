<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\ImportQueue;

use NewApiBundle\Component\Import\IdentityChecker;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Workflow\ImportQueueTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class IdentitySubscriber implements EventSubscriberInterface
{
    /**
     * @var IdentityChecker
     */
    private $identityChecker;

    public function __construct(IdentityChecker $identityChecker)
    {
        $this->identityChecker = $identityChecker;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import_queue.guard.'.ImportQueueTransitions::SUSPICIOUS => ['guardIfQueueItemIsSuspicious'],
            'workflow.import_queue.transition.'.ImportQueueTransitions::SUSPICIOUS => ['validateDuplicities'],
        ];
    }

    /**
     * @param TransitionEvent $transitionEvent
     */
    public function validateDuplicities(TransitionEvent $transitionEvent): void
    {
        /** @var ImportQueue $item */
        $item = $transitionEvent->getSubject();
        $this->identityChecker->validateItem($item);
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardIfQueueItemIsSuspicious(GuardEvent $guardEvent): void
    {
        /** @var ImportQueue $item */
        $item = $guardEvent->getSubject();
        $duplicities = $this->identityChecker->getItemDuplicities($item);
        if (count($duplicities) === 0) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Queue Item has no duplicity', '0'));
        }
    }
}
