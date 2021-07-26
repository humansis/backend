<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

class HeaderColumnReview implements \JsonSerializable
{
    /** @var string|null */
    private $columnName;
    /** @var string|null */
    private $message;

    /**
     * HeaderColumnReview constructor.
     *
     * @param string|null $columnName
     * @param string|null $message
     */
    public function __construct(?string $columnName, ?string $message)
    {
        $this->columnName = $columnName;
        $this->message = $message;
    }

    /**
     * @return string|null
     */
    public function getColumnName(): ?string
    {
        return $this->columnName;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function jsonSerialize(): array
    {
        return [
            'column' => $this->columnName,
            'message' => $this->message,
        ];
    }
}
