<?php

namespace VoucherBundle\DTO;

/**
 * stores statistics from repository
 */
class PurchaseSummary
{
    /** @var int */
    private $count;

    /** @var float */
    private $value;

    /**
     * SmartcardPurchaseSummary constructor.
     *
     * @param int   $count
     * @param float $value
     */
    public function __construct(int $count, float $value)
    {
        $this->count = $count;
        $this->value = $value;
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
