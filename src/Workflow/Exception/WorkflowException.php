<?php

declare(strict_types=1);

namespace Workflow\Exception;

use RuntimeException;
use Throwable;

class WorkflowException extends RuntimeException
{
    public function __construct(string $currentState, $message = "Workflow is in invalid state")
    {
        parent::__construct($message . " state='$currentState'", 0, null);
    }
}
