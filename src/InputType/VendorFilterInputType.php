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

#[Assert\GroupSequence(['VendorFilterInputType', 'Strict'])]
class VendorFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
    use FulltextFilterTrait;
    use LocationFilterTrait;

    /**
     * @var string|null
     */
    #[Enum(options: [
        'enumClass' => "Enum\VendorInvoicingState",
    ])]
    protected $invoicing;

    public function hasInvoicing(): bool
    {
        return $this->has('invoicing');
    }

    /**
     * @throws EnumValueNoFoundException
     */
    public function getInvoicing(): string
    {
        return VendorInvoicingState::valueFromAPI($this->invoicing);
    }
}
