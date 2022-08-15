<?php

namespace NewApiBundle\DTO;

/**
 * stores statistics from repository
 */
class PurchaseSummary
{
    /** @var int */
    private $count;

    /** @var string decimal */
    private $value;

    /**
     * SmartcardPurchaseSummary constructor.
     *
     * @param int   $count
     * @param mixed $value
     */
    public function __construct(int $count, $value)
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
    public function getValue()
    {
        return $this->value;
    }
}
