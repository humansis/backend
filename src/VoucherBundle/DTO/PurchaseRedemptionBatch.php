<?php

namespace VoucherBundle\DTO;

/**
 * stores statistics from repository
 */
class PurchaseRedemptionBatch
{
    /** @var int */
    private $count;

    /** @var float */
    private $value;

    /** @var int[] */
    private $purchasesIds;

    /**
     * PurchaseBatchToRedeem constructor.
     *
     * @param int   $count
     * @param float $value
     * @param int[] $purchasesIds
     */
    public function __construct(int $count, float $value, array $purchasesIds)
    {
        $this->count = $count;
        $this->value = $value;
        $this->purchasesIds = $purchasesIds;
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

    /**
     * @return int[]
     */
    public function getPurchasesIds(): array
    {
        return $this->purchasesIds;
    }
}
