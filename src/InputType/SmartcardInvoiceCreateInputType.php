<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['SmartcardInvoiceCreateInputType', 'Strict'])]
class SmartcardInvoiceCreateInputType implements InputTypeInterface
{
    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
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
