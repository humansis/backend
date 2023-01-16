<?php

declare(strict_types=1);

namespace InputType;

use Entity\Referral;
use Enum\VulnerabilityCriteria;
use Enum\Livelihood;
use Enum\ResidencyStatus;
use InputType\FilterFragment\FulltextFilterTrait;
use InputType\FilterFragment\LocationFilterTrait;
use InputType\FilterFragment\PrimaryIdFilterTrait;
use InputType\FilterFragment\ProjectFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['HouseholdFilterInputType', 'Strict'])]
class HouseholdFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
    use FulltextFilterTrait;
    use ProjectFilterTrait;
    use LocationFilterTrait;

    #[Assert\Choice(['M', 'F'])]
    protected $gender;

    #[Assert\All(constraints: [new Assert\Choice(callback: 'vulnerabilities', strict: true, groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    protected $vulnerabilities;

    #[Assert\All(constraints: [new Assert\Type('integer', groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    protected $nationalIds;

    #[Assert\All(
        constraints: [
            new Assert\Choice(callback: [ResidencyStatus::class, "all"]),
        ],
        groups: ['Strict']
    )]
    #[Assert\Type('array')]
    protected $residencyStatuses;

    #[Assert\All(
        constraints: [
            new Assert\Choice(callback: [Referral::class, "types"]),
        ],
        groups: ['Strict']
    )]
    #[Assert\Type('array')]
    protected $referralTypes;

    #[Assert\All(
        constraints: [
            new Assert\Choice(callback: [Livelihood::class, "values"], strict: true, groups: ['Strict']),
        ],
        groups: ['Strict']
    )]
    #[Assert\Type('array')]
    protected $livelihoods;

    public static function vulnerabilities(): array
    {
        return array_keys(VulnerabilityCriteria::all());
    }

    public function hasGender(): bool
    {
        return $this->has('gender');
    }

    public function getGender()
    {
        return $this->gender;
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
}
