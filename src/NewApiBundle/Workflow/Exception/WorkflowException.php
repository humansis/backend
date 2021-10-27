<?php declare(strict_types=1);

namespace NewApiBundle\Workflow\Exception;

use Throwable;

class WorkflowException extends \RuntimeException
{
    public function __construct($message = "Workflow is in invalid state", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
