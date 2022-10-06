<?php

declare(strict_types=1);

namespace InputType;

use Enum\EnumValueNoFoundException;
use Enum\VendorInvoicingState;
use InputType\FilterFragment\FulltextFilterTrait;
use InputType\FilterFragment\LocationFilterTrait;
use InputType\FilterFragment\PrimaryIdFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints\Enum;

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
     * @Enum(enumClass="Enum\VendorInvoicingState")
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
     * @throws EnumValueNoFoundException
     */
    public function getInvoicing(): string
    {
        return VendorInvoicingState::valueFromAPI($this->invoicing);
    }
}
