<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\ImportQueue;

use NewApiBundle\Component\Import\DuplicityResolver;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Workflow\ImportQueueTransitions;
use NewApiBundle\Workflow\ImportTransitions;
use NewApiBundle\Workflow\WorkflowTool;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\WorkflowInterface;

class DuplicityResolveSubscriber implements EventSubscriberInterface
{
    /**
     * @var DuplicityResolver
     */
    private $duplicityResolver;

    /**
     * @var WorkflowInterface
     */
    private $importStateMachine;

    public function __construct(DuplicityResolver $duplicityResolver, WorkflowInterface $importStateMachine)
    {
        $this->duplicityResolver = $duplicityResolver;
        $this->importStateMachine = $importStateMachine;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import_queue.transition.'.ImportQueueTransitions::TO_IGNORE => ['resolveDuplicity'],
            'workflow.import_queue.transition.'.ImportQueueTransitions::TO_LINK => ['resolveDuplicity'],
            'workflow.import_queue.transition.'.ImportQueueTransitions::TO_UPDATE => ['resolveDuplicity'],
            'workflow.import_queue.transition.'.ImportQueueTransitions::TO_CREATE => ['resolveDuplicity'],
            'workflow.import_queue.entered.'.ImportQueueTransitions::TO_IGNORE => ['resolveImport'],
            'workflow.import_queue.entered.'.ImportQueueTransitions::TO_LINK => ['resolveImport'],
            'workflow.import_queue.entered.'.ImportQueueTransitions::TO_UPDATE => ['resolveImport'],
            'workflow.import_queue.entered.'.ImportQueueTransitions::TO_CREATE => ['resolveImport'],
        ];
    }

    /**
     * @param EnteredEvent $enteredEvent
     */
    public function resolveImport(EnteredEvent $enteredEvent): void
    {
        /** @var ImportQueue $importQueue */
        $importQueue = $enteredEvent->getSubject();
        WorkflowTool::checkAndApply($this->importStateMachine, $importQueue->getImport(),
            [ImportTransitions::RESOLVE_IDENTITY_DUPLICITIES, ImportTransitions::RESOLVE_SIMILARITY_DUPLICITIES], false);
    }

    /**
     * @param TransitionEvent $transitionEvent
     */
    public function resolveDuplicity(TransitionEvent $transitionEvent): void
    {
        $context = $transitionEvent->getContext();

        // this transition is only for user resolving
        if (!isset($context['resolve'])) {
            return;
        }

        /** @var ImportQueue $importQueue */
        $importQueue = $transitionEvent->getSubject();
        $duplicityId = (int) $context['duplicityId'];
        $user = $context['user'];
        $this->duplicityResolver->resolve($importQueue, $duplicityId, $transitionEvent->getTransition()->getName(), $user);
    }
}
