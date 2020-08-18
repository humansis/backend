<?php

namespace ProjectBundle\Entity;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\Institution;
use DistributionBundle\Entity\DistributionData;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

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
        return $assistance instanceof DistributionData && $this->isDistributionAllowed();
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
}
