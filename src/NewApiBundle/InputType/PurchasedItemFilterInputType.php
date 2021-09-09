<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\InputType\FilterFragment\FulltextFilterTrait;
use NewApiBundle\InputType\FilterFragment\LocationFilterTrait;
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

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $assistances;


    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("string", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $modalityTypes;

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

    /**
     * @Iso8601
     */
    protected $dateFrom;

    /**
     * @Iso8601
     */
    protected $dateTo;

    public function hasModalityTypes(): bool
    {
        return $this->has('modalityTypes');
    }

    /**
     * @return string[]
     */
    public function getModalityTypes(): array
    {
        return $this->modalityTypes;
    }

    public function hasAssistances(): bool
    {
        return $this->has('assistances');
    }

    /**
     * @return int[]
     */
    public function getAssistances(): array
    {
        return $this->assistances;
    }

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

    /**
     * @return string
     */
    public function getDateFrom(): string
    {
        return $this->dateFrom;
    }

    public function hasDateFrom(): bool
    {
        return $this->has('dateFrom');
    }

    /**
     * @return string
     */
    public function getDateTo(): string
    {
        return $this->dateTo;
    }

    public function hasDateTo(): bool
    {
        return $this->has('dateTo');
    }
}
