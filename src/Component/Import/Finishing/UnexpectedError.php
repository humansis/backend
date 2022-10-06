<?php

declare(strict_types=1);

namespace Component\Import\Finishing;

use Exception;
use JsonSerializable;

final class UnexpectedError implements JsonSerializable
{
    /** @var string */
    private $finishAction;

    /** @var string */
    private $errorMessage;

    /** @var string[] */
    private $stackTrace;

    /**
     * @param string $finishAction
     * @param string $errorMessage
     * @param string[] $stackTrace
     */
    private function __construct(string $finishAction, string $errorMessage, array $stackTrace)
    {
        $this->finishAction = $finishAction;
        $this->errorMessage = $errorMessage;
        $this->stackTrace = $stackTrace;
    }

    public static function create(string $finishAction, Exception $exception): self
    {
        return new UnexpectedError($finishAction, $exception->getMessage(), $exception->getTrace());
    }

    public function jsonSerialize(): array
    {
        return [
            'action' => $this->finishAction,
            'message' => $this->errorMessage,
            'trace' => $this->stackTrace,
        ];
    }
}
