<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\ImportQueue;

use NewApiBundle\Component\Import\DuplicityResolver;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Workflow\ImportQueueTransitions;
use NewApiBundle\Workflow\ImportTransitions;
use NewApiBundle\Workflow\WorkflowTool;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
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
            // 'workflow.import_queue.transition.'.ImportQueueTransitions::TO_IGNORE => ['resolveDuplicity'],
            // 'workflow.import_queue.transition.'.ImportQueueTransitions::TO_LINK => ['resolveDuplicity'],
            // 'workflow.import_queue.transition.'.ImportQueueTransitions::TO_UPDATE => ['resolveDuplicity'],
            // 'workflow.import_queue.transition.'.ImportQueueTransitions::TO_CREATE => ['resolveDuplicity'],
            'workflow.import_queue.completed.'.ImportQueueTransitions::TO_IGNORE => ['resolveImport'],
            'workflow.import_queue.completed.'.ImportQueueTransitions::TO_LINK => ['resolveImport'],
            'workflow.import_queue.completed.'.ImportQueueTransitions::TO_UPDATE => ['resolveImport'],
            'workflow.import_queue.completed.'.ImportQueueTransitions::TO_CREATE => ['resolveImport'],
        ];
    }

    /**
     * @param CompletedEvent $enteredEvent
     */
    public function resolveImport(CompletedEvent $enteredEvent): void
    {
        /** @var ImportQueue $importQueue */
        $importQueue = $enteredEvent->getSubject();
        if ($importQueue->getImport()->getState() === ImportState::IDENTITY_CHECK_FAILED
        || $importQueue->getImport()->getState() === ImportState::SIMILARITY_CHECK_FAILED) {
            echo "Item#{$importQueue->getId()} state {$importQueue->getImport()->getState()}\n";
            echo ImportTransitions::RESOLVE_IDENTITY_DUPLICITIES."\n";
            foreach ($this->importStateMachine->buildTransitionBlockerList($importQueue->getImport(),
                ImportTransitions::RESOLVE_IDENTITY_DUPLICITIES) as $block) {
                echo "cant go bcs ".$block->getMessage()."\n";
            }
            echo ImportTransitions::RESOLVE_SIMILARITY_DUPLICITIES."\n";
            foreach ($this->importStateMachine->buildTransitionBlockerList($importQueue->getImport(),
                ImportTransitions::RESOLVE_SIMILARITY_DUPLICITIES) as $block) {
                echo "cant go bcs ".$block->getMessage()."\n";
            }
        }
        WorkflowTool::checkAndApply($this->importStateMachine, $importQueue->getImport(),
            [ImportTransitions::RESOLVE_IDENTITY_DUPLICITIES, ImportTransitions::RESOLVE_SIMILARITY_DUPLICITIES], false);
    }
}
