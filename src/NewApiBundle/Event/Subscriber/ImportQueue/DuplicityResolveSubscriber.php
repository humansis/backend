<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\ImportQueue;

use NewApiBundle\Component\Import\DuplicityResolver;
use NewApiBundle\Workflow\ImportQueueTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

class DuplicityResolveSubscriber implements EventSubscriberInterface
{
    /**
     * @var DuplicityResolver
     */
    private $duplicityResolver;

    public function __construct(DuplicityResolver $duplicityResolver)
    {
        $this->duplicityResolver = $duplicityResolver;
    }

    public function enteredDuplicity(TransitionEvent $enteredEvent): void
    {

    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.importQueue.entered.'.ImportQueueTransitions::TO_CREATE => ['enteredDuplicity'],
            'workflow.importQueue.entered.'.ImportQueueTransitions::TO_UPDATE => ['enteredDuplicity'],
            'workflow.importQueue.entered.'.ImportQueueTransitions::TO_LINK => ['enteredDuplicity'],
            'workflow.importQueue.entered.'.ImportQueueTransitions::TO_IGNORE => ['enteredDuplicity'],
        ];
    }


}
