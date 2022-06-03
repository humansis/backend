<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

final class QueueViolation
{
    /**
     * @var string
     */
    private $column;

    /**
     * @var string
     */
    private $message;

    private $value;

    /**
     * @var int
     */
    private $lineIndex;

    public function __construct(int $lineIndex, string $column, string $message, $value)
    {
        $this->column = ucfirst($column);
        $this->message = $message;
        $this->value = $value;
        $this->lineIndex = $lineIndex;
    }

    /**
     * @param int    $lineIndex
     * @param string $column
     * @param string $message
     * @param        $value
     *
     * @return QueueViolation
     */
    public static function create(int $lineIndex, string $column, string $message, $value): QueueViolation
    {
        return new static($lineIndex, $column, $message, $value);
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * @param string $column
     */
    public function setColumn(string $column): void
    {
        $this->column = $column;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
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

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getLineIndex(): int
    {
        return $this->lineIndex;
    }

    /**
     * @param int $lineIndex
     */
    public function setLineIndex(int $lineIndex): void
    {
        $this->lineIndex = $lineIndex;
    }

}
