<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"SmartcardRedemptionBatchCreateInputType", "Strict"})
 */
class SmartcardRedemptionBatchCreateInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    private $purchaseIds;

    /**
     * @return int[]
     */
    public function getPurchaseIds(): array
    {
        return $this->purchaseIds;
    }

    /**
     * @param int[] $purchaseIds
     */
    public function setPurchaseIds(array $purchaseIds)
    {
        $this->purchaseIds = $purchaseIds;
    }
}
