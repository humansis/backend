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
     * @param mixed $value
     * @param int[] $purchasesIds
     */
    public function __construct($value, array $purchasesIds)
    {
        $this->value = $value;
        $this->purchasesIds = $purchasesIds;
    }

    /**
     * @return float
     */
    public function getValue()
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
