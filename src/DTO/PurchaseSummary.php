<?php

namespace DTO;

/**
 * stores statistics from repository
 */
class PurchaseSummary
{
    /**
     * SmartcardPurchaseSummary constructor.
     */
    public function __construct(private readonly int $count, private readonly mixed $value)
    {
    }

    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }
}
