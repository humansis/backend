<?php

namespace Entity;

use DateTimeInterface;
use DBAL\HouseholdAssetsEnum;
use DBAL\HouseholdSupportReceivedTypeEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use DBAL\HouseholdShelterStatusEnum;
use Entity\Helper\CountryDependent;
use Entity\Helper\EnumTrait;
use Enum\HouseholdAssets;
use Enum\HouseholdShelterStatus;
use DBAL\LivelihoodEnum;
use Enum\HouseholdSupportReceivedType;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Household
 *
 * @method Household setCountryIso3(string $countryIso3)
 */
#[ORM\Table(name: 'household')]
#[ORM\Entity(repositoryClass: 'Repository\HouseholdRepository')]
class Household extends AbstractBeneficiary
{
    use EnumTrait;
    use CountryDependent;

    #[ORM\Column(name: 'livelihood', type: 'enum_livelihood', nullable: true)]
    private string|null $livelihood = null;

    /**
     * @var int[]
     */
    #[ORM\Column(name: 'assets', type: 'json', nullable: true)]
    private array|null $assets;

    /**
     * TODO: migrate to enum sometimes
     */
    #[ORM\Column(name: 'shelter_status', type: 'integer', nullable: true)]
    private int|null $shelterStatus = null;

    #[ORM\Column(name: 'notes', type: 'string', length: 255, nullable: true)]
    private string|null $notes = null;

    #[ORM\Column(name: 'latitude', type: 'string', length: 45, nullable: true)]
    private string|null $latitude = null;

    #[ORM\Column(name: 'longitude', type: 'string', length: 45, nullable: true)]
    private string|null $longitude = null;

    /**
     * @var Collection | CountrySpecific[]
     */
    #[ORM\OneToMany(mappedBy: 'household', targetEntity: 'Entity\CountrySpecificAnswer', cascade: ['persist', 'remove'])]
    private Collection | array $countrySpecificAnswers;

    /**
     * @var Collection|Beneficiary[]
     */
    #[SymfonyGroups(['FullHousehold', 'SmallHousehold', 'FullReceivers'])]
    #[ORM\OneToMany(mappedBy: 'household', targetEntity: 'Entity\Beneficiary', cascade: ['persist'], fetch: 'EAGER')]
    private array | Collection $beneficiaries;

    #[ORM\Column(name: 'income', type: 'integer', nullable: true)]
    private int|null $income = null;

    #[ORM\Column(name: 'foodConsumptionScore', type: 'integer', nullable: true)]
    private int|null $foodConsumptionScore = null;

    #[ORM\Column(name: 'copingStrategiesIndex', type: 'integer', nullable: true)]
    private int|null $copingStrategiesIndex = null;

    #[ORM\OneToMany(mappedBy: 'household', targetEntity: 'Entity\HouseholdLocation', cascade: ['persist', 'remove'])]
    private $householdLocations;

    #[ORM\Column(name: 'debt_level', type: 'integer', nullable: true)]
    private int|null $debtLevel = null;

    /**
     * @var int[]
     */
    #[ORM\Column(name: 'support_received_types', type: 'json', nullable: true)]
    private array $supportReceivedTypes;

    #[ORM\Column(name: 'support_organization_name', type: 'string', nullable: true)]
    private string|null $supportOrganizationName = null;

    #[ORM\Column(name: 'support_date_received', type: 'date', nullable: true)]
    private ?DateTimeInterface $supportDateReceived = null;

    #[ORM\Column(name: 'income_spent_on_food', type: 'integer', nullable: true)]
    private int|null $incomeSpentOnFood = null;

    #[ORM\Column(name: 'household_income', type: 'integer', nullable: true)]
    private int|null $householdIncome = null;

    #[ORM\Column(name: 'enumerator_name', type: 'string', nullable: true)]
    private string|null $enumeratorName = null;

    #[ORM\OneToOne(targetEntity: 'Entity\Person')]
    #[ORM\JoinColumn(name: 'proxy_id')]
    private ?Person $proxy = null;

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
     *
     */
    public function setLivelihood(?string $livelihood): self
    {
        $this->livelihood = LivelihoodEnum::valueToDB($livelihood);

        return $this;
    }

    /**
     * Get livelihood.
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
        return array_values(
            array_map(fn($asset) => HouseholdAssetsEnum::valueFromDB($asset), $this->assets)
        );
    }

    /**
     * @param string[] $assets
     */
    public function setAssets(array $assets): self
    {
        self::validateValues('assets', HouseholdAssets::class, $assets);
        $this->assets = array_values(
            array_unique(
                array_map(fn($asset) => HouseholdAssetsEnum::valueToDB($asset), $assets)
            )
        );

        return $this;
    }

    /**
     * @see HouseholdShelterStatus::values()
     */
    public function getShelterStatus(): ?string
    {
        return HouseholdShelterStatusEnum::valueFromDB($this->shelterStatus);
    }

    /**
     * @see HouseholdShelterStatus::values()
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
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
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
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
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
    public function getBeneficiaries(bool $showArchived = false): Collection |array
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

    public function getNumberDependents(): int
    {
        return count($this->getBeneficiaries()) - 1;
    }

    /**
     * Set income.
     *
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
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeHouseholdLocation(HouseholdLocation $householdLocation)
    {
        return $this->householdLocations->removeElement($householdLocation);
    }

    /**
     * Add householdLocation.
     *
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

    public function getDebtLevel(): ?int
    {
        return $this->debtLevel;
    }

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
        return array_values(
            array_map(fn($type) => HouseholdSupportReceivedTypeEnum::valueFromDB($type), $this->supportReceivedTypes)
        );
    }

    /**
     * @param string[] $supportReceivedTypes
     */
    public function setSupportReceivedTypes(array $supportReceivedTypes): self
    {
        self::validateValues('supportReceivedType', HouseholdSupportReceivedType::class, $supportReceivedTypes);
        $this->supportReceivedTypes = array_values(
            array_map(fn($type) => HouseholdSupportReceivedTypeEnum::valueToDB($type), $supportReceivedTypes)
        );

        return $this;
    }

    public function getSupportOrganizationName(): ?string
    {
        return $this->supportOrganizationName;
    }

    public function setSupportOrganizationName(?string $supportOrganizationName): self
    {
        $this->supportOrganizationName = $supportOrganizationName;

        return $this;
    }

    public function getSupportDateReceived(): ?DateTimeInterface
    {
        return $this->supportDateReceived;
    }

    public function setSupportDateReceived(?DateTimeInterface $supportDateReceived): self
    {
        $this->supportDateReceived = $supportDateReceived;

        return $this;
    }

    public function setIncomeSpentOnFood(?int $incomeSpentOnFood): self
    {
        $this->incomeSpentOnFood = $incomeSpentOnFood;

        return $this;
    }

    public function setHouseholdIncome(?int $householdIncome): self
    {
        $this->householdIncome = $householdIncome;

        return $this;
    }

    public function getIncomeSpentOnFood(): ?int
    {
        return $this->incomeSpentOnFood;
    }

    public function getHouseholdIncome(): ?int
    {
        return $this->householdIncome;
    }

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

    public function getEnumeratorName(): ?string
    {
        return $this->enumeratorName;
    }

    public function setEnumeratorName(?string $enumeratorName): Household
    {
        $this->enumeratorName = $enumeratorName;

        return $this;
    }

    public function getProxy(): ?Person
    {
        return $this->proxy;
    }

    public function setProxy(?Person $proxy): void
    {
        $this->proxy = $proxy;
    }

    public function addMember(Beneficiary $beneficiary)
    {
        $this->beneficiaries->add($beneficiary);
    }
}
