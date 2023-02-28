<?php

declare(strict_types=1);

namespace Component\Import\Integrity;

final class QueueViolation
{
    private string $column;

    public function __construct(private int $lineIndex, string $column, private string $message, private $value)
    {
        $this->column = ucfirst($column);
    }

    /**
     * @param        $value
     *
     */
    public static function create(int $lineIndex, string $column, string $message, $value): QueueViolation
    {
        return new static($lineIndex, $column, $message, $value);
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function setColumn(string $column): void
    {
        $this->column = $column;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getLineIndex(): int
    {
        return $this->lineIndex;
    }

    public function setLineIndex(int $lineIndex): void
    {
        $this->lineIndex = $lineIndex;
    }
}
