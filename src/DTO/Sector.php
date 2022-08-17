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
    /** @var string */
    private $sectorName;

    /** @var string|null */
    private $subSectorName;

    /** @var bool */
    private $distributionAllowed = false;

    /** @var bool */
    private $activityAllowed = false;

    /** @var bool */
    private $beneficiaryAllowed = false;

    /** @var bool */
    private $householdAllowed = false;

    /** @var bool */
    private $communityAllowed = false;

    /** @var bool */
    private $institutionAllowed = false;

    /**
     * Sector constructor.
     *
     * @param string      $sectorName
     * @param string|null $subSectorName
     */
    public function __construct(string $sectorName, ?string $subSectorName)
    {
        $this->sectorName = $sectorName;
        $this->subSectorName = $subSectorName;
    }

    /**
     * @return string
     */
    public function getSectorName(): string
    {
        return $this->sectorName;
    }

    /**
     * @param string $sectorName
     */
    public function setSectorName(string $sectorName): self
    {
        $this->sectorName = $sectorName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSubSectorName(): ?string
    {
        return $this->subSectorName;
    }

    /**
     * @param string|null $subSectorName
     */
    public function setSubSectorName(?string $subSectorName): self
    {
        $this->subSectorName = $subSectorName;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDistributionAllowed(): bool
    {
        return $this->distributionAllowed;
    }

    /**
     * @param bool $distributionAllowed
     */
    public function setDistributionAllowed(bool $distributionAllowed = true): self
    {
        $this->distributionAllowed = $distributionAllowed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActivityAllowed(): bool
    {
        return $this->activityAllowed;
    }

    /**
     * @param bool $activityAllowed
     */
    public function setActivityAllowed(bool $activityAllowed = true): self
    {
        $this->activityAllowed = $activityAllowed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isBeneficiaryAllowed(): bool
    {
        return $this->beneficiaryAllowed;
    }

    /**
     * @param bool $beneficiaryAllowed
     */
    public function setBeneficiaryAllowed(bool $beneficiaryAllowed = true): self
    {
        $this->beneficiaryAllowed = $beneficiaryAllowed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHouseholdAllowed(): bool
    {
        return $this->householdAllowed;
    }

    /**
     * @param bool $householdAllowed
     */
    public function setHouseholdAllowed(bool $householdAllowed = true): self
    {
        $this->householdAllowed = $householdAllowed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCommunityAllowed(): bool
    {
        return $this->communityAllowed;
    }

    /**
     * @param bool $communityAllowed
     */
    public function setCommunityAllowed(bool $communityAllowed = true): self
    {
        $this->communityAllowed = $communityAllowed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isInstitutionAllowed(): bool
    {
        return $this->institutionAllowed;
    }

    /**
     * @param bool $institutionAllowed
     */
    public function setInstitutionAllowed(bool $institutionAllowed = true): self
    {
        $this->institutionAllowed = $institutionAllowed;

        return $this;
    }

    /**
     * @param object $assistance
     *
     * @return bool
     */
    public function supportsAssistance(object $assistance): bool
    {
        return $assistance instanceof Assistance && $this->isDistributionAllowed();
    }

    /**
     * @param object $target
     *
     * @return bool
     */
    public function supportsTarget(object $target): bool
    {
        return $target instanceof Beneficiary && $this->isBeneficiaryAllowed()
            || $target instanceof Household && $this->isHouseholdAllowed()
            || $target instanceof Institution && $this->isInstitutionAllowed()
            || $target instanceof Community && $this->isCommunityAllowed()
            ;
    }

    /**
     * @param string $assistanceType
     * @return bool
     */
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

    /**
     * @param string $assistanceTarget
     * @return bool
     */
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
