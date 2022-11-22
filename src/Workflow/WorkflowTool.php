<?php

declare(strict_types=1);

namespace Workflow;

use Workflow\Exception\WorkflowException;
use Symfony\Component\Workflow\WorkflowInterface;

class WorkflowTool
{
    /**
     * @param WorkflowInterface $workflow
     * @param object $subject
     * @param array $transitions
     * @param bool $throw
     */
    public static function checkAndApply(
        WorkflowInterface $workflow,
        object $subject,
        array $transitions,
        bool $throw = true
    ) {
        $enabledTransitions = [];
        foreach ($transitions as $transition) {
            if ($workflow->can($subject, $transition)) {
                $enabledTransitions[] = $transition;
            }
        }

        if (count($enabledTransitions) == 1 || (count($enabledTransitions) > 1 && !$throw)) {
            $workflow->apply($subject, $enabledTransitions[0]);
        } elseif (count($enabledTransitions) > 1 && $throw) {
            throw new WorkflowException(
                $subject->getState(),
                'There too many enabled transitions: [' . implode(', ', $enabledTransitions) . ']'
            );
        } elseif ($throw) {
            throw new WorkflowException($subject->getState(), 'There is no enabled transition.');
        }
    }
}
