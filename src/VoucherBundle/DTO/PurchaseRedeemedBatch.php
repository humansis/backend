<?php

namespace VoucherBundle\DTO;

/**
 * stores statistics from repository
 */
class PurchaseRedeemedBatch
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
}
