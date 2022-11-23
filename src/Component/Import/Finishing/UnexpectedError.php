<?php

declare(strict_types=1);

namespace Component\Import\Finishing;

use Exception;
use JsonSerializable;

final class UnexpectedError implements JsonSerializable
{
    /**
     * @param string[] $stackTrace
     */
    private function __construct(private readonly string $finishAction, private readonly string $errorMessage, private readonly array $stackTrace)
    {
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
