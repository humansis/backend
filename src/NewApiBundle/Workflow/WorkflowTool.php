<?php declare(strict_types=1);

namespace NewApiBundle\Workflow;

use NewApiBundle\Workflow\Exception\WorkflowException;
use Symfony\Component\Workflow\WorkflowInterface;

class WorkflowTool
{
    /**
     * @param WorkflowInterface $workflow
     * @param object            $subject
     * @param array             $transitions
     * @param bool              $throw
     */
    public static function checkAndApply(WorkflowInterface $workflow, object $subject, array $transitions, bool $throw = true)
    {
        foreach ($transitions as $transition) {
            if ($workflow->can($subject, $transition)) {
                $workflow->apply($subject, $transition);

                return;
            }
        }

        if ($throw) {
            throw new WorkflowException('There is no enabled transition');
        }
    }
}
