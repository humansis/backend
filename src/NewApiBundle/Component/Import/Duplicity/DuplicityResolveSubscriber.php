<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Duplicity;

use NewApiBundle\Component\Import\Entity\Queue;
use NewApiBundle\Component\Import\Enum\State;
use NewApiBundle\Component\Import\Enum\QueueTransitions;
use NewApiBundle\Component\Import\Enum\Transitions;
use NewApiBundle\Workflow\WorkflowTool;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
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
            // 'workflow.import_queue.transition.'.QueueTransitions::TO_IGNORE => ['resolveDuplicity'],
            // 'workflow.import_queue.transition.'.QueueTransitions::TO_LINK => ['resolveDuplicity'],
            // 'workflow.import_queue.transition.'.QueueTransitions::TO_UPDATE => ['resolveDuplicity'],
            // 'workflow.import_queue.transition.'.QueueTransitions::TO_CREATE => ['resolveDuplicity'],
            'workflow.import_queue.completed.'.QueueTransitions::TO_IGNORE => ['resolveImport'],
            'workflow.import_queue.completed.'.QueueTransitions::TO_LINK => ['resolveImport'],
            'workflow.import_queue.completed.'.QueueTransitions::TO_UPDATE => ['resolveImport'],
            'workflow.import_queue.completed.'.QueueTransitions::TO_CREATE => ['resolveImport'],
        ];
    }

    /**
     * @param CompletedEvent $enteredEvent
     */
    public function resolveImport(CompletedEvent $enteredEvent): void
    {
        /** @var Queue $importQueue */
        $importQueue = $enteredEvent->getSubject();
        if ($importQueue->getImport()->getState() === State::IDENTITY_CHECK_FAILED
        || $importQueue->getImport()->getState() === State::SIMILARITY_CHECK_FAILED) {
            echo "Item#{$importQueue->getId()} state {$importQueue->getImport()->getState()}\n";
            echo Transitions::RESOLVE_IDENTITY_DUPLICITIES."\n";
            foreach ($this->importStateMachine->buildTransitionBlockerList($importQueue->getImport(),
                Transitions::RESOLVE_IDENTITY_DUPLICITIES) as $block) {
                echo "cant go bcs ".$block->getMessage()."\n";
            }
            echo Transitions::RESOLVE_SIMILARITY_DUPLICITIES."\n";
            foreach ($this->importStateMachine->buildTransitionBlockerList($importQueue->getImport(),
                Transitions::RESOLVE_SIMILARITY_DUPLICITIES) as $block) {
                echo "cant go bcs ".$block->getMessage()."\n";
            }
        }
        WorkflowTool::checkAndApply($this->importStateMachine, $importQueue->getImport(),
            [Transitions::RESOLVE_IDENTITY_DUPLICITIES, Transitions::RESOLVE_SIMILARITY_DUPLICITIES], false);
    }
}
