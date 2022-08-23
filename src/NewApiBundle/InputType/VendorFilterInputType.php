<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Enum\VendorInvoicingState;
use NewApiBundle\InputType\FilterFragment\FulltextFilterTrait;
use NewApiBundle\InputType\FilterFragment\LocationFilterTrait;
use NewApiBundle\InputType\FilterFragment\PrimaryIdFilterTrait;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;
use NewApiBundle\Validator\Constraints\Enum;

/**
 * @Assert\GroupSequence({"VendorFilterInputType", "Strict"})
 */
class VendorFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
    use FulltextFilterTrait;
    use LocationFilterTrait;

    /**
     * @var string|null
     * @Enum(enumClass="NewApiBundle\Enum\VendorInvoicingState")
     */
    protected $invoicing;

    /**
     * @return bool
     */
    public function hasInvoicing(): bool
    {
        return $this->has('invoicing');
    }

    /**
     * @return string
     * @throws \NewApiBundle\Enum\EnumValueNoFoundException
     */
    public function getInvoicing(): string
    {
        return VendorInvoicingState::valueFromAPI($this->invoicing);
    }

}
