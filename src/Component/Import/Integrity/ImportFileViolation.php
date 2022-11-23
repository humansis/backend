<?php

declare(strict_types=1);

namespace Component\Import\Integrity;

use JsonSerializable;

class ImportFileViolation implements JsonSerializable
{
    /**
     * HeaderColumnReview constructor.
     *
     * @param string[] $columns
     */
    public function __construct(private readonly string $message, private readonly ?array $columns = null)
    {
    }

    /**
     * @return string[]|null
     */
    public function getColumns(): ?array
    {
        return $this->columns;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function jsonSerialize(): array
    {
        return [
            'columns' => $this->columns,
            'message' => $this->message,
        ];
    }
}
