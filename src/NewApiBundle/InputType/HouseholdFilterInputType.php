<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use BeneficiaryBundle\Entity\Referral;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"HouseholdFilterInputType", "Strict"})
 */
class HouseholdFilterInputType extends AbstractFilterInputType
{
    /**
     * @Assert\Type("string")
     */
    protected $fulltext;

    /**
     * @Assert\Choice({"M", "F"}, strict=true)
     */
    protected $gender;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $projects;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $vulnerabilities;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $nationalIds;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback={"BeneficiaryBundle\Enum\ResidencyStatus", "all"}, strict=true)
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $residencyStatuses;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback="referralTypes", strict=true)
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $referralTypes;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *        @Assert\Choice(callback={"ProjectBundle\Enum\Livelihood", "values"}, strict=true, strict=true)
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $livelihoods;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $locations;

    final public static function referralTypes()
    {
        return array_keys(Referral::REFERRALTYPES);
    }

    public function hasFulltext(): bool
    {
        return $this->has('fulltext');
    }

    public function getFulltext()
    {
        return $this->fulltext;
    }

    public function hasGender(): bool
    {
        return $this->has('gender');
    }

    public function getGender()
    {
        return $this->gender;
    }

    public function hasProjects(): bool
    {
        return $this->has('projects');
    }

    public function getProjects()
    {
        return $this->projects;
    }

    public function hasVulnerabilities(): bool
    {
        return $this->has('vulnerabilities');
    }

    public function getVulnerabilities()
    {
        return $this->vulnerabilities;
    }

    public function hasNationalIds(): bool
    {
        return $this->has('nationalIds');
    }

    public function getNationalIds()
    {
        return $this->nationalIds;
    }

    public function hasResidencyStatuses(): bool
    {
        return $this->has('residencyStatuses');
    }

    public function getResidencyStatuses()
    {
        return $this->residencyStatuses;
    }

    public function hasReferralTypes(): bool
    {
        return $this->has('referralTypes');
    }

    public function getReferralTypes()
    {
        return $this->referralTypes;
    }

    public function hasLivelihoods(): bool
    {
        return $this->has('livelihoods');
    }

    public function getLivelihoods()
    {
        return $this->livelihoods;
    }

    public function hasLocations(): bool
    {
        return $this->has('locations');
    }

    public function getLocations()
    {
        return $this->locations;
    }
}
