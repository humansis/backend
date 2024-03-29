<?php

declare(strict_types=1);

namespace InputType\FilterFragment;

use Symfony\Component\Validator\Constraints as Assert;
use Happyr\Validator\Constraint\EntityExist;

trait VendorFilterTrait
{
    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"}),
     *         @EntityExist(entity="\Entity\Vendor", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
    protected $vendors;

    public function hasVendors(): bool
    {
        return $this->has('vendors');
    }

    /**
     * @return int[]
     */
    public function getVendors(): ?array
    {
        return $this->vendors;
    }
}
