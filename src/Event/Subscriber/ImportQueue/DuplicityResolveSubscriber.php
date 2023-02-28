<?php

declare(strict_types=1);

namespace Event\Subscriber\ImportQueue;

use Component\Import\DuplicityResolver;
use Entity\ImportQueue;
use Enum\ImportState;
use Workflow\ImportQueueTransitions;
use Workflow\ImportTransitions;
use Workflow\WorkflowTool;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\WorkflowInterface;

class DuplicityResolveSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly DuplicityResolver $duplicityResolver, private readonly WorkflowInterface $importStateMachine)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // 'workflow.import_queue.transition.'.ImportQueueTransitions::TO_IGNORE => ['resolveDuplicity'],
            // 'workflow.import_queue.transition.'.ImportQueueTransitions::TO_LINK => ['resolveDuplicity'],
            // 'workflow.import_queue.transition.'.ImportQueueTransitions::TO_UPDATE => ['resolveDuplicity'],
            // 'workflow.import_queue.transition.'.ImportQueueTransitions::TO_CREATE => ['resolveDuplicity'],
            'workflow.import_queue.completed.' . ImportQueueTransitions::TO_IGNORE => ['resolveImport'],
            'workflow.import_queue.completed.' . ImportQueueTransitions::TO_LINK => ['resolveImport'],
            'workflow.import_queue.completed.' . ImportQueueTransitions::TO_UPDATE => ['resolveImport'],
            'workflow.import_queue.completed.' . ImportQueueTransitions::TO_CREATE => ['resolveImport'],
        ];
    }

    public function resolveImport(CompletedEvent $enteredEvent): void
    {
        /** @var ImportQueue $importQueue */
        $importQueue = $enteredEvent->getSubject();
        WorkflowTool::checkAndApply(
            $this->importStateMachine,
            $importQueue->getImport(),
            [ImportTransitions::RESOLVE_IDENTITY_DUPLICITIES, ImportTransitions::RESOLVE_SIMILARITY_DUPLICITIES],
            false
        );
    }
}
