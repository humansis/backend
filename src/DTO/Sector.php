<?php

namespace DTO;

use Entity\Beneficiary;
use Entity\Community;
use Entity\Household;
use Entity\Institution;
use Entity\Assistance;
use Enum\AssistanceTargetType;
use Enum\AssistanceType;
use InvalidArgumentException;

/**
 * Sector DTO
 */
class Sector
{
    private bool $distributionAllowed = false;

    private bool $activityAllowed = false;

    private bool $beneficiaryAllowed = false;

    private bool $householdAllowed = false;

    private bool $communityAllowed = false;

    private bool $institutionAllowed = false;

    /**
     * Sector constructor.
     */
    public function __construct(private string $sectorName, private ?string $subSectorName)
    {
    }

    public function getSectorName(): string
    {
        return $this->sectorName;
    }

    public function setSectorName(string $sectorName): self
    {
        $this->sectorName = $sectorName;

        return $this;
    }

    public function getSubSectorName(): ?string
    {
        return $this->subSectorName;
    }

    public function setSubSectorName(?string $subSectorName): self
    {
        $this->subSectorName = $subSectorName;

        return $this;
    }

    public function isDistributionAllowed(): bool
    {
        return $this->distributionAllowed;
    }

    public function setDistributionAllowed(bool $distributionAllowed = true): self
    {
        $this->distributionAllowed = $distributionAllowed;

        return $this;
    }

    public function isActivityAllowed(): bool
    {
        return $this->activityAllowed;
    }

    public function setActivityAllowed(bool $activityAllowed = true): self
    {
        $this->activityAllowed = $activityAllowed;

        return $this;
    }

    public function isBeneficiaryAllowed(): bool
    {
        return $this->beneficiaryAllowed;
    }

    public function setBeneficiaryAllowed(bool $beneficiaryAllowed = true): self
    {
        $this->beneficiaryAllowed = $beneficiaryAllowed;

        return $this;
    }

    public function isHouseholdAllowed(): bool
    {
        return $this->householdAllowed;
    }

    public function setHouseholdAllowed(bool $householdAllowed = true): self
    {
        $this->householdAllowed = $householdAllowed;

        return $this;
    }

    public function isCommunityAllowed(): bool
    {
        return $this->communityAllowed;
    }

    public function setCommunityAllowed(bool $communityAllowed = true): self
    {
        $this->communityAllowed = $communityAllowed;

        return $this;
    }

    public function isInstitutionAllowed(): bool
    {
        return $this->institutionAllowed;
    }

    public function setInstitutionAllowed(bool $institutionAllowed = true): self
    {
        $this->institutionAllowed = $institutionAllowed;

        return $this;
    }

    public function supportsAssistance(object $assistance): bool
    {
        return $assistance instanceof Assistance && $this->isDistributionAllowed();
    }

    public function supportsTarget(object $target): bool
    {
        return $target instanceof Beneficiary && $this->isBeneficiaryAllowed()
            || $target instanceof Household && $this->isHouseholdAllowed()
            || $target instanceof Institution && $this->isInstitutionAllowed()
            || $target instanceof Community && $this->isCommunityAllowed();
    }

    public function isAssistanceTypeAllowed(string $assistanceType): bool
    {
        if (!in_array($assistanceType, AssistanceType::values())) {
            throw new InvalidArgumentException('This assistance type is not supported');
        }

        if ($assistanceType === AssistanceType::ACTIVITY && $this->isActivityAllowed()) {
            return true;
        }

        if ($assistanceType === AssistanceType::DISTRIBUTION && $this->isDistributionAllowed()) {
            return true;
        }

        return false;
    }

    public function isAssistanceTargetAllowed(string $assistanceTarget): bool
    {
        if (!in_array($assistanceTarget, AssistanceTargetType::values())) {
            throw new InvalidArgumentException('This assistance target type is not supported');
        }

        if ($assistanceTarget === AssistanceTargetType::COMMUNITY && $this->isCommunityAllowed()) {
            return true;
        }

        if ($assistanceTarget === AssistanceTargetType::HOUSEHOLD && $this->isHouseholdAllowed()) {
            return true;
        }

        if ($assistanceTarget === AssistanceTargetType::INDIVIDUAL && $this->isBeneficiaryAllowed()) {
            return true;
        }

        if ($assistanceTarget === AssistanceTargetType::INSTITUTION && $this->isInstitutionAllowed()) {
            return true;
        }

        return false;
    }
}
