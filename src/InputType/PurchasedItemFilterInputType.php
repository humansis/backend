<?php

declare(strict_types=1);

namespace InputType;

use Enum\BeneficiaryType;
use InputType\FilterFragment\AssistanceFilterTrait;
use InputType\FilterFragment\DateIntervalFilterTrait;
use InputType\FilterFragment\FulltextFilterTrait;
use InputType\FilterFragment\LocationFilterTrait;
use InputType\FilterFragment\ModalityTypeFilterTrait;
use InputType\FilterFragment\ProjectFilterTrait;
use InputType\FilterFragment\VendorFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['PurchasedItemFilterInputType', 'Strict'])]
class PurchasedItemFilterInputType extends AbstractFilterInputType
{
    use FulltextFilterTrait;
    use ProjectFilterTrait;
    use VendorFilterTrait;
    use LocationFilterTrait;
    use DateIntervalFilterTrait;
    use AssistanceFilterTrait;
    use ModalityTypeFilterTrait;

    #[Assert\All(
        constraints: [
            new Assert\Choice(callback: [BeneficiaryType::class, "values"]),
        ],
        groups: ['Strict']
    )]
    #[Assert\Type('array')]
    protected $beneficiaryTypes;

    public function hasBeneficiaryTypes(): bool
    {
        return $this->has('beneficiaryTypes');
    }

    /**
     * @return string[]
     */
    public function getBeneficiaryTypes(): array
    {
        return $this->beneficiaryTypes;
    }
}
