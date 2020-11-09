<?php

namespace VoucherBundle\DTO;

/**
 * stores statistics from repository
 */
class PurchaseRedeemedBatch implements \JsonSerializable
{
    /** @var \DateTimeInterface */
    private $date;

    /** @var int */
    private $count;

    /** @var float */
    private $value;

    /**
     * PurchaseRedeemBatch constructor.
     *
     * @param \DateTimeInterface $date
     * @param int                $count
     * @param float              $value
     */
    public function __construct(\DateTimeInterface $date, int $count, float $value)
    {
        $this->date = $date;
        $this->count = $count;
        $this->value = $value;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    public function jsonSerialize()
    {
        return [
            'datetime' => $this->date ? $this->date->format('U') : null,
            'date' => $this->date ? $this->date->format('d-m-Y H:i') : null,
            'count' => $this->count,
            'value' => $this->value,
        ];
    }
}
