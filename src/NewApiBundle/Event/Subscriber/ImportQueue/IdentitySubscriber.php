<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\ImportQueue;

use NewApiBundle\Component\Import\IdentityChecker;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Workflow\ImportQueueTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\GuardEvent;
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

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardSuspicious(GuardEvent $guardEvent): void
    {
        /** @var ImportQueue $item */
        $item = $guardEvent->getSubject();
        $valid = $this->identityChecker->isValidItem($item);
        if ($valid === true) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Queue Item is valid', '0'));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.importQueue.guard.'.ImportQueueTransitions::SUSPICIOUS => ['guardSuspicious'],
        ];
    }
}
