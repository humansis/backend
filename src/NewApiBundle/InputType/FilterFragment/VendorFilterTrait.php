<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\FilterFragment;
use Symfony\Component\Validator\Constraints as Assert;

trait VendorFilterTrait
{
    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $vendors;

    /**
     * @return bool
     */
    public function hasVendors(): bool
    {
        return $this->has('vendors');
    }

    /**
     * @return int[]
     */
    public function getVendors()
    {
        return $this->vendors;
    }
}
