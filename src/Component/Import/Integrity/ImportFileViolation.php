<?php

declare(strict_types=1);

namespace Component\Import\Integrity;

use JsonSerializable;

class ImportFileViolation implements JsonSerializable
{
    /** @var string[]|null */
    private $columns;

    /** @var string */
    private $message;

    /**
     * HeaderColumnReview constructor.
     *
     * @param string $message
     * @param string[] $columns
     */
    public function __construct(string $message, ?array $columns = null)
    {
        $this->message = $message;
        $this->columns = $columns;
    }

    /**
     * @return string[]|null
     */
    public function getColumns(): ?array
    {
        return $this->columns;
    }

    /**
     * @return string
     */
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
