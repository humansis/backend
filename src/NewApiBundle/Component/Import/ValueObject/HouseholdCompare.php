<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\ValueObject;

class HouseholdCompare
{
    /** @var ScalarCompare|null */
    private $livelihood;
    
    /** @var ListCompare|null */
    private $assets;
    
    /** @var ScalarCompare|null */
    private $shelterStatus;
    
    /** @var ScalarCompare|null */
    private $notes;
    
    /** @var ScalarCompare|null */
    private $latitude;
    
    /** @var ScalarCompare|null */
    private $longitude;
    
    /** @var ListCompare|null */
    private $countrySpecificAnswers;
    
    /** @var ScalarCompare|null */
    private $income;
    
    /** @var ScalarCompare|null */
    private $foodConsumptionScore;
    
    /** @var ScalarCompare|null */
    private $copingStrategiesIndex;
    
    /** @var ScalarCompare|null */
    private $location;
    
    /** @var ScalarCompare|null */
    private $adms;
    
    /** @var ScalarCompare|null */
    private $adm1;
    
    /** @var ScalarCompare|null */
    private $adm2;
    
    /** @var ScalarCompare|null */
    private $adm3;
    
    /** @var ScalarCompare|null */
    private $adm4;
    
    /** @var ScalarCompare|null */
    private $debtLevel;
    
    /** @var ScalarCompare|null */
    private $supportReceivedTypes;
    
    /** @var ScalarCompare|null */
    private $supportOrganizationName;
    
    /** @var ScalarCompare|null */
    private $supportDateReceived;
    
    /** @var ScalarCompare|null */
    private $incomeSpentOnFood;
    
    /** @var ScalarCompare|null */
    private $householdIncome;
    
    /** @var ScalarCompare|null */
    private $enumeratorName;
    
    public function isSame(): bool
    {
        return null === $this->livelihood
            && null === $this->assets
            && null === $this->shelterStatus
            && null === $this->notes
            && null === $this->latitude
            && null === $this->longitude
            && null === $this->countrySpecificAnswers
            && null === $this->income
            && null === $this->foodConsumptionScore
            && null === $this->copingStrategiesIndex
            && null === $this->location
            && null === $this->adms
            && null === $this->adm1
            && null === $this->adm2
            && null === $this->adm3
            && null === $this->adm4
            && null === $this->debtLevel
            && null === $this->supportReceivedTypes
            && null === $this->supportOrganizationName
            && null === $this->supportDateReceived
            && null === $this->incomeSpentOnFood
            && null === $this->householdIncome
            && null === $this->enumeratorName
            ;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getLivelihood(): ?ScalarCompare
    {
        return $this->livelihood;
    }

    /**
     * @param ScalarCompare|null $livelihood
     */
    public function setLivelihood(?ScalarCompare $livelihood): void
    {
        $this->livelihood = $livelihood;
    }

    /**
     * @return ListCompare|null
     */
    public function getAssets(): ?ListCompare
    {
        return $this->assets;
    }

    /**
     * @param ListCompare|null $assets
     */
    public function setAssets(?ListCompare $assets): void
    {
        $this->assets = $assets;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getShelterStatus(): ?ScalarCompare
    {
        return $this->shelterStatus;
    }

    /**
     * @param ScalarCompare|null $shelterStatus
     */
    public function setShelterStatus(?ScalarCompare $shelterStatus): void
    {
        $this->shelterStatus = $shelterStatus;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getNotes(): ?ScalarCompare
    {
        return $this->notes;
    }

    /**
     * @param ScalarCompare|null $notes
     */
    public function setNotes(?ScalarCompare $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getLatitude(): ?ScalarCompare
    {
        return $this->latitude;
    }

    /**
     * @param ScalarCompare|null $latitude
     */
    public function setLatitude(?ScalarCompare $latitude): void
    {
        $this->latitude = $latitude;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getLongitude(): ?ScalarCompare
    {
        return $this->longitude;
    }

    /**
     * @param ScalarCompare|null $longitude
     */
    public function setLongitude(?ScalarCompare $longitude): void
    {
        $this->longitude = $longitude;
    }

    /**
     * @return ListCompare|null
     */
    public function getCountrySpecificAnswers(): ?ListCompare
    {
        return $this->countrySpecificAnswers;
    }

    /**
     * @param ListCompare|null $countrySpecificAnswers
     */
    public function setCountrySpecificAnswers(?ListCompare $countrySpecificAnswers): void
    {
        $this->countrySpecificAnswers = $countrySpecificAnswers;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getIncome(): ?ScalarCompare
    {
        return $this->income;
    }

    /**
     * @param ScalarCompare|null $income
     */
    public function setIncome(?ScalarCompare $income): void
    {
        $this->income = $income;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getFoodConsumptionScore(): ?ScalarCompare
    {
        return $this->foodConsumptionScore;
    }

    /**
     * @param ScalarCompare|null $foodConsumptionScore
     */
    public function setFoodConsumptionScore(?ScalarCompare $foodConsumptionScore): void
    {
        $this->foodConsumptionScore = $foodConsumptionScore;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getCopingStrategiesIndex(): ?ScalarCompare
    {
        return $this->copingStrategiesIndex;
    }

    /**
     * @param ScalarCompare|null $copingStrategiesIndex
     */
    public function setCopingStrategiesIndex(?ScalarCompare $copingStrategiesIndex): void
    {
        $this->copingStrategiesIndex = $copingStrategiesIndex;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getLocation(): ?ScalarCompare
    {
        return $this->location;
    }

    /**
     * @param ScalarCompare|null $location
     */
    public function setLocation(?ScalarCompare $location): void
    {
        $this->location = $location;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getAdms(): ?ScalarCompare
    {
        return $this->adms;
    }

    /**
     * @param ScalarCompare|null $adms
     */
    public function setAdms(?ScalarCompare $adms): void
    {
        $this->adms = $adms;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getAdm1(): ?ScalarCompare
    {
        return $this->adm1;
    }

    /**
     * @param ScalarCompare|null $adm1
     */
    public function setAdm1(?ScalarCompare $adm1): void
    {
        $this->adm1 = $adm1;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getAdm2(): ?ScalarCompare
    {
        return $this->adm2;
    }

    /**
     * @param ScalarCompare|null $adm2
     */
    public function setAdm2(?ScalarCompare $adm2): void
    {
        $this->adm2 = $adm2;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getAdm3(): ?ScalarCompare
    {
        return $this->adm3;
    }

    /**
     * @param ScalarCompare|null $adm3
     */
    public function setAdm3(?ScalarCompare $adm3): void
    {
        $this->adm3 = $adm3;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getAdm4(): ?ScalarCompare
    {
        return $this->adm4;
    }

    /**
     * @param ScalarCompare|null $adm4
     */
    public function setAdm4(?ScalarCompare $adm4): void
    {
        $this->adm4 = $adm4;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getDebtLevel(): ?ScalarCompare
    {
        return $this->debtLevel;
    }

    /**
     * @param ScalarCompare|null $debtLevel
     */
    public function setDebtLevel(?ScalarCompare $debtLevel): void
    {
        $this->debtLevel = $debtLevel;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getSupportReceivedTypes(): ?ScalarCompare
    {
        return $this->supportReceivedTypes;
    }

    /**
     * @param ScalarCompare|null $supportReceivedTypes
     */
    public function setSupportReceivedTypes(?ScalarCompare $supportReceivedTypes): void
    {
        $this->supportReceivedTypes = $supportReceivedTypes;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getSupportOrganizationName(): ?ScalarCompare
    {
        return $this->supportOrganizationName;
    }

    /**
     * @param ScalarCompare|null $supportOrganizationName
     */
    public function setSupportOrganizationName(?ScalarCompare $supportOrganizationName): void
    {
        $this->supportOrganizationName = $supportOrganizationName;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getSupportDateReceived(): ?ScalarCompare
    {
        return $this->supportDateReceived;
    }

    /**
     * @param ScalarCompare|null $supportDateReceived
     */
    public function setSupportDateReceived(?ScalarCompare $supportDateReceived): void
    {
        $this->supportDateReceived = $supportDateReceived;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getIncomeSpentOnFood(): ?ScalarCompare
    {
        return $this->incomeSpentOnFood;
    }

    /**
     * @param ScalarCompare|null $incomeSpentOnFood
     */
    public function setIncomeSpentOnFood(?ScalarCompare $incomeSpentOnFood): void
    {
        $this->incomeSpentOnFood = $incomeSpentOnFood;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getHouseholdIncome(): ?ScalarCompare
    {
        return $this->householdIncome;
    }

    /**
     * @param ScalarCompare|null $householdIncome
     */
    public function setHouseholdIncome(?ScalarCompare $householdIncome): void
    {
        $this->householdIncome = $householdIncome;
    }

    /**
     * @return ScalarCompare|null
     */
    public function getEnumeratorName(): ?ScalarCompare
    {
        return $this->enumeratorName;
    }

    /**
     * @param ScalarCompare|null $enumeratorName
     */
    public function setEnumeratorName(?ScalarCompare $enumeratorName): void
    {
        $this->enumeratorName = $enumeratorName;
    }

}
