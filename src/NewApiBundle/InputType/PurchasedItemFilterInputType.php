<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\InputType\FilterFragment\FulltextFilterTrait;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use NewApiBundle\Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"PurchasedItemFilterInputType", "Strict"})
 */
class PurchasedItemFilterInputType extends AbstractFilterInputType
{
    use FulltextFilterTrait;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $projects;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $locations;

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
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $vendors;

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

    public function hasProjects(): bool
    {
        return $this->has('projects');
    }

    /**
     * @return int[]
     */
    public function getProjects(): array
    {
        return $this->projects;
    }

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

    public function hasLocations(): bool
    {
        return $this->has('locations');
    }

    /**
     * @return int[]
     */
    public function getLocations(): array
    {
        return $this->locations;
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
