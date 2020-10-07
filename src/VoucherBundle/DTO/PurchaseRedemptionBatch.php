<?php

namespace VoucherBundle\DTO;

/**
 * stores statistics from repository
 */
class PurchaseRedemptionBatch
{
    /** @var float */
    private $value;

    /** @var int[] */
    private $purchasesIds;

    /**
     * PurchaseBatchToRedeem constructor.
     *
     * @param float $value
     * @param int[] $purchasesIds
     */
    public function __construct(float $value, array $purchasesIds)
    {
        $this->value = $value;
        $this->purchasesIds = $purchasesIds;
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
