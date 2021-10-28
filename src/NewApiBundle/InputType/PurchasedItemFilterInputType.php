<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\InputType\FilterFragment\AssistanceFilterTrait;
use NewApiBundle\InputType\FilterFragment\DateIntervalFilterTrait;
use NewApiBundle\InputType\FilterFragment\FulltextFilterTrait;
use NewApiBundle\InputType\FilterFragment\LocationFilterTrait;
use NewApiBundle\InputType\FilterFragment\ModalityTypeFilterTrait;
use NewApiBundle\InputType\FilterFragment\ProjectFilterTrait;
use NewApiBundle\InputType\FilterFragment\VendorFilterTrait;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use NewApiBundle\Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"PurchasedItemFilterInputType", "Strict"})
 */
class PurchasedItemFilterInputType extends AbstractFilterInputType
{
    use FulltextFilterTrait;
    use ProjectFilterTrait;
    use VendorFilterTrait;
    use LocationFilterTrait;
    use DateIntervalFilterTrait;
    use AssistanceFilterTrait;
    use ModalityTypeFilterTrait;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback={"NewApiBundle\Enum\BeneficiaryType", "values"})
     *     },
     *     groups={"Strict"}
     * )
     */
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
