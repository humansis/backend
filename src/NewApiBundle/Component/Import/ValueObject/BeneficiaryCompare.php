<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\ValueObject;

class BeneficiaryCompare
{
    /** @var ScalarCompare|null */
    private $householdId;

    /** @var ScalarCompare|null */
    private $localFullName;

    /** @var ScalarCompare|null */
    private $englishFullName;

    /** @var ScalarCompare|null */
    private $gender;

    /** @var ScalarCompare|null */
    private $dateOfBirth;

    /** @var ScalarCompare|null */
    private $phone1;

    /** @var ScalarCompare|null */
    private $phone2;

    /** @var ScalarCompare|null */
    private $vulnerability;

    /** @var ListCompare|null */
    private $residencyStatus;

    /**
     * @return ScalarCompare|null
     */
    public function getHouseholdId(): ?ScalarCompare
    {
        return $this->householdId;
    }

    /**
     * @param ScalarCompare|null $householdId
     */
    public function setHouseholdId(?ScalarCompare $householdId): void
    {
        $this->householdId = $householdId;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getLocalFullName(): ?ScalarCompare
    {
        return $this->localFullName;
    }

    /**
     * @param ScalarCompare|null $localFullName
     */
    public function setLocalFullName(?ScalarCompare $localFullName): void
    {
        $this->localFullName = $localFullName;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getEnglishFullName(): ?ScalarCompare
    {
        return $this->englishFullName;
    }

    /**
     * @param ScalarCompare|null $englishFullName
     */
    public function setEnglishFullName(?ScalarCompare $englishFullName): void
    {
        $this->englishFullName = $englishFullName;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getGender(): ?ScalarCompare
    {
        return $this->gender;
    }

    /**
     * @param ScalarCompare|null $gender
     */
    public function setGender(?ScalarCompare $gender): void
    {
        $this->gender = $gender;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getDateOfBirth(): ?ScalarCompare
    {
        return $this->dateOfBirth;
    }

    /**
     * @param ScalarCompare|null $dateOfBirth
     */
    public function setDateOfBirth(?ScalarCompare $dateOfBirth): void
    {
        $this->dateOfBirth = $dateOfBirth;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getPhone1(): ?ScalarCompare
    {
        return $this->phone1;
    }

    /**
     * @param ScalarCompare|null $phone1
     */
    public function setPhone1(?ScalarCompare $phone1): void
    {
        $this->phone1 = $phone1;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getPhone2(): ?ScalarCompare
    {
        return $this->phone2;
    }

    /**
     * @param ScalarCompare|null $phone2
     */
    public function setPhone2(?ScalarCompare $phone2): void
    {
        $this->phone2 = $phone2;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getVulnerability(): ?ScalarCompare
    {
        return $this->vulnerability;
    }

    /**
     * @param ScalarCompare|null $vulnerability
     */
    public function setVulnerability(?ScalarCompare $vulnerability): void
    {
        $this->vulnerability = $vulnerability;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getResidencyStatus(): ?ScalarCompare
    {
        return $this->residencyStatus;
    }

    /**
     * @param ListCompare|null $residencyStatus
     */
    public function setResidencyStatus(?ListCompare $residencyStatus): void
    {
        $this->residencyStatus = $residencyStatus;
    }

}
