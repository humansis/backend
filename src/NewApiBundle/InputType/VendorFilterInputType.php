<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Enum\VariableBool;
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
     * @var bool|null
     * @Enum(enumClass="NewApiBundle\Enum\VariableBool")
     */
    protected $isInvoiced;

    public function hasIsInvoiced(): bool
    {
        return $this->has('isInvoiced');
    }

    /**
     * @return bool|null
     * @throws \NewApiBundle\Enum\EnumValueNoFoundException
     */
    public function getIsInvoiced(): ?bool
    {
        return VariableBool::valueFromAPI($this->isInvoiced);
    }
}
