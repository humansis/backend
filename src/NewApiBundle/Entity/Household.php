<?php

namespace NewApiBundle\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\DBAL\HouseholdAssetsEnum;
use NewApiBundle\DBAL\HouseholdShelterStatusEnum;
use NewApiBundle\DBAL\HouseholdSupportReceivedTypeEnum;
use NewApiBundle\Entity\Helper\EnumTrait;
use NewApiBundle\Entity\ImportHouseholdDuplicity;
use NewApiBundle\Enum\HouseholdAssets;
use NewApiBundle\Enum\HouseholdShelterStatus;
use NewApiBundle\Enum\HouseholdSupportReceivedType;
use ProjectBundle\DBAL\LivelihoodEnum;

/**
 * Household
 *
 * @ORM\Table(name="household")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\HouseholdRepository")
 */
class Household extends AbstractBeneficiary
{
    use EnumTrait;

    /**
     * @var string|null
     *
     * @ORM\Column(name="livelihood", type="enum_livelihood", nullable=true)
     */
    private $livelihood;

    /**
     * @var int[]
     *
     * @ORM\Column(name="assets", type="array", nullable=true)
     */
    private $assets;

    /**
     * TODO: migrate to enum sometimes
     * @var int
     *
     * @ORM\Column(name="shelter_status", type="integer", nullable=true)
     */
    private $shelterStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="string", length=255, nullable=true)
     */
    private $notes;

    /**
     * @var string
     *
     * @ORM\Column(name="latitude", type="string", length=45, nullable=true)
     */
    private $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="string", length=45, nullable=true)
     */
    private $longitude;

    /**
     * @var CountrySpecificAnswer
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\CountrySpecificAnswer", mappedBy="household", cascade={"persist", "remove"})
     */
    private $countrySpecificAnswers;

    /**
     * @var Collection|Beneficiary[]
     *
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\Beneficiary", mappedBy="household", fetch="EAGER", cascade={"persist"})
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers"})
     */
    private $beneficiaries;

    /**
     * @var int|null
     *
     * @ORM\Column(name="income", type="integer", nullable=true)
     */
    private $income;

    /**
     * @var int
     *
     * @ORM\Column(name="foodConsumptionScore", type="integer", nullable=true)
     */
    private $foodConsumptionScore;

    /**
     * @var int
     *
     * @ORM\Column(name="copingStrategiesIndex", type="integer", nullable=true)
     */
    private $copingStrategiesIndex;

    /**
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\HouseholdLocation", mappedBy="household", cascade={"persist", "remove"})
     */
    private $householdLocations;

    /**
     * @var int
     *
     * @ORM\Column(name="debt_level", type="integer", nullable=true)
     */
    private $debtLevel;

    /**
     * @var int[]
     *
     * @ORM\Column(name="support_received_types", type="array", nullable=true)
     */
    private $supportReceivedTypes;

    /**
     * @var string|null
     *
     * @ORM\Column(name="support_organization_name", type="string", nullable=true)
     */
    private $supportOrganizationName;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="support_date_received", type="date", nullable=true)
     */
    private $supportDateReceived;

    /**
     * @var int|null
     *
     * @ORM\Column(name="income_spent_on_food", type="integer", nullable=true)
     */
    private $incomeSpentOnFood;

    /**
     * @var int|null
     *
     * @ORM\Column(name="household_income", type="integer", nullable=true)
     */
    private $householdIncome;

    /**
     * @var string|null
     *
     * @ORM\Column(name="enumerator_name", type="string", nullable=true)
     */
    private $enumeratorName = null;

    /**
     * @var Person|null
     *
     * @ORM\OneToOne(targetEntity="NewApiBundle\Entity\Person")
     * @ORM\JoinColumn(name="proxy_id")
     */
    private $proxy;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->countrySpecificAnswers = new ArrayCollection();
        $this->beneficiaries = new ArrayCollection();
        $this->householdLocations = new ArrayCollection();

        $this->assets = [];
        $this->supportReceivedTypes = [];
    }

    /**
     * Set livelihood.
     *
     * @param string|null $livelihood
     *
     * @return self
     */
    public function setLivelihood(?string $livelihood): self
    {
        $this->livelihood = LivelihoodEnum::valueToDB($livelihood);

        return $this;
    }

    /**
     * Get livelihood.
     *
     * @return string|null
     */
    public function getLivelihood(): ?string
    {
        return LivelihoodEnum::valueFromDB($this->livelihood);
    }

    /**
     * @return string[]
     */
    public function getAssets(): array
    {
        return array_values(array_map(function ($asset) {
            return HouseholdAssetsEnum::valueFromDB($asset);
        }, $this->assets));
    }

    /**
     * @param string[] $assets
     *
     * @return self
     */
    public function setAssets(array $assets): self
    {
        self::validateValues('assets', HouseholdAssets::class, $assets);
        $this->assets = array_values(array_unique(array_map(function ($asset) {
            return HouseholdAssetsEnum::valueToDB($asset);
        }, $assets)));

        return $this;
    }

    /**
     * @see HouseholdShelterStatus::values()
     * @return string|null
     */
    public function getShelterStatus(): ?string
    {
        return HouseholdShelterStatusEnum::valueFromDB($this->shelterStatus);
    }

    /**
     * @see HouseholdShelterStatus::values()
     * @param string|null $shelterStatus
     *
     * @return self
     */
    public function setShelterStatus(?string $shelterStatus): self
    {
        self::validateValue('shelterStatus', HouseholdShelterStatus::class, $shelterStatus, true);

        $this->shelterStatus = HouseholdShelterStatusEnum::valueToDB($shelterStatus);

        return $this;
    }

    /**
     * Set notes.
     *
     * @param string $notes
     *
     * @return Household
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes.
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set lat.
     *
     * @param string $latitude
     *
     * @return Household
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get lat.
     *
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set long.
     *
     * @param string $longitude
     *
     * @return Household
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get long.
     *
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set beneficiaries.
     *
     * @param Collection|null $collection
     *
     * @return Household
     */
    public function setBeneficiaries(Collection $collection = null)
    {
        $this->beneficiaries = $collection;

        return $this;
    }

    /**
     * Set countrySpecificAnswer.
     *
     * @param Collection|null $collection
     *
     * @return Household
     */
    public function setCountrySpecificAnswers(Collection $collection = null)
    {
        $this->countrySpecificAnswers[] = $collection;

        return $this;
    }

    /**
     * Add countrySpecificAnswer.
     *
     * @param CountrySpecificAnswer $countrySpecificAnswer
     *
     * @return Household
     */
    public function addCountrySpecificAnswer(CountrySpecificAnswer $countrySpecificAnswer)
    {
        $this->countrySpecificAnswers[] = $countrySpecificAnswer;

        return $this;
    }

    /**
     * Remove countrySpecificAnswer.
     *
     * @param CountrySpecificAnswer $countrySpecificAnswer
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCountrySpecificAnswer(CountrySpecificAnswer $countrySpecificAnswer)
    {
        return $this->countrySpecificAnswers->removeElement($countrySpecificAnswer);
    }

    /**
     * Get countrySpecificAnswers.
     *
     * @return Collection
     */
    public function getCountrySpecificAnswers()
    {
        return $this->countrySpecificAnswers;
    }

    /**
     * Add beneficiary.
     *
     * @param Beneficiary $beneficiary
     *
     * @return Household
     */
    public function addBeneficiary(Beneficiary $beneficiary)
    {
        $this->beneficiaries->add($beneficiary);

        return $this;
    }

    /**
     * Remove beneficiary.
     *
     * @param Beneficiary $beneficiary
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeBeneficiary(Beneficiary $beneficiary)
    {
        return $this->beneficiaries->removeElement($beneficiary);
    }

    /**
     * Get beneficiaries.
     *
     * @return Collection|Beneficiary[]
     */
    public function getBeneficiaries(bool $showArchived = false)
    {
        $criteria = Criteria::create();
        if (!$showArchived) {
            $criteria->where(Criteria::expr()->eq('archived', false));
        }
        return $this->beneficiaries->matching($criteria);
    }

    /**
     * Reset the list of beneficiaries
     */
    public function resetBeneficiaries()
    {
        $this->beneficiaries = new ArrayCollection();

        return $this;
    }

    /**
     * @return int
     */
    public function getNumberDependents(): int
    {
        return count($this->getBeneficiaries()) - 1;
    }

    /**
     * Set income.
     *
     * @param int|null $income
     *
     * @return Household
     */
    public function setIncome(?int $income)
    {
        $this->income = $income;

        return $this;
    }

    /**
     * Get income.
     *
     * @return int|null
     */
    public function getIncome(): ?int
    {
        return $this->income;
    }

    /**
     * Set foodConsumptionScore.
     *
     * @param int $foodConsumptionScore
     *
     * @return Household
     */
    public function setFoodConsumptionScore($foodConsumptionScore)
    {
        $this->foodConsumptionScore = $foodConsumptionScore;

        return $this;
    }

    /**
     * Get foodConsumptionScore.
     *
     * @return int
     */
    public function getFoodConsumptionScore()
    {
        return $this->foodConsumptionScore;
    }

    /**
     * Set copingStrategiesIndex.
     *
     * @param int $copingStrategiesIndex
     *
     * @return Household
     */
    public function setCopingStrategiesIndex($copingStrategiesIndex)
    {
        $this->copingStrategiesIndex = $copingStrategiesIndex;

        return $this;
    }

    /**
     * Get copingStrategiesIndex.
     *
     * @return int
     */
    public function getCopingStrategiesIndex()
    {
        return $this->copingStrategiesIndex;
    }

    /**
     * Remove householdLocation.
     *
     * @param HouseholdLocation $householdLocation
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeHouseholdLocation(HouseholdLocation $householdLocation)
    {
        return $this->householdLocations->removeElement($householdLocation);
    }

    /**
     * Add householdLocation.
     *
     * @param HouseholdLocation $householdLocation
     *
     * @return Household
     */
    public function addHouseholdLocation(HouseholdLocation $householdLocation)
    {
        $this->householdLocations[] = $householdLocation;
        $householdLocation->setHousehold($this);
        return $this;
    }

    /**
     * Get householdLocations.
     *
     * @return Collection
     */
    public function getHouseholdLocations()
    {
        return $this->householdLocations;
    }

    /**
     * @return int|null
     */
    public function getDebtLevel(): ?int
    {
        return $this->debtLevel;
    }

    /**
     * @param int|null $debtLevel
     *
     * @return self
     */
    public function setDebtLevel(?int $debtLevel): self
    {
        $this->debtLevel = $debtLevel;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getSupportReceivedTypes(): array
    {
        return array_values(array_map(function ($type) {
            return HouseholdSupportReceivedTypeEnum::valueFromDB($type);
        }, $this->supportReceivedTypes));
    }

    /**
     * @param string[] $supportReceivedTypes
     *
     * @return self
     */
    public function setSupportReceivedTypes(array $supportReceivedTypes): self
    {
        self::validateValues('supportReceivedType', HouseholdSupportReceivedType::class, $supportReceivedTypes);
        $this->supportReceivedTypes = array_values(array_map(function ($type) {
            return HouseholdSupportReceivedTypeEnum::valueToDB($type);
        }, $supportReceivedTypes));

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSupportOrganizationName(): ?string
    {
        return $this->supportOrganizationName;
    }

    /**
     * @param string|null $supportOrganizationName
     *
     * @return self
     */
    public function setSupportOrganizationName(?string $supportOrganizationName): self
    {
        $this->supportOrganizationName = $supportOrganizationName;

        return $this;
    }


    /**
     * @return DateTimeInterface|null
     */
    public function getSupportDateReceived(): ?DateTimeInterface
    {
        return $this->supportDateReceived;
    }

    /**
     * @param DateTimeInterface|null $supportDateReceived
     *
     * @return self
     */
    public function setSupportDateReceived(?DateTimeInterface $supportDateReceived): self
    {
        $this->supportDateReceived = $supportDateReceived;

        return $this;
    }

    /**
     * @param int|null $incomeSpentOnFood
     *
     * @return self
     */
    public function setIncomeSpentOnFood(?int $incomeSpentOnFood): self
    {
        $this->incomeSpentOnFood = $incomeSpentOnFood;

        return $this;
    }

    /**
     * @param int|null $householdIncome
     *
     * @return self
     */
    public function setHouseholdIncome(?int $householdIncome): self
    {
        $this->householdIncome = $householdIncome;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getIncomeSpentOnFood(): ?int
    {
        return $this->incomeSpentOnFood;
    }

    /**
     * @return int|null
     */
    public function getHouseholdIncome(): ?int
    {
        return $this->householdIncome;
    }


    /**
     * @return Beneficiary|null
     */
    public function getHouseholdHead(): ?Beneficiary
    {
        $householdHead = null;
        /** @var Beneficiary $beneficiary */
        foreach ($this->getBeneficiaries() as $beneficiary) {
            if ($beneficiary->isHead()) {
                $householdHead = $beneficiary;
                break;
            }
        }

        return $householdHead;
    }

    /**
     * @return string|null
     */
    public function getEnumeratorName(): ?string
    {
        return $this->enumeratorName;
    }

    /**
     * @param string|null $enumeratorName
     *
     * @return Household
     */
    public function setEnumeratorName(?string $enumeratorName): Household
    {
        $this->enumeratorName = $enumeratorName;

        return $this;
    }

    /**
     * @return Person|null
     */
    public function getProxy(): ?Person
    {
        return $this->proxy;
    }

    /**
     * @param Person|null $proxy
     */
    public function setProxy(?Person $proxy): void
    {
        $this->proxy = $proxy;
    }

    /**
     * @param Beneficiary $beneficiary
     */
    public function addMember(Beneficiary $beneficiary)
    {
        $this->beneficiaries->add($beneficiary);
    }

}
