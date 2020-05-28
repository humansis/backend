<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Type as JMS_Type;
use JMS\Serializer\Annotation\Groups;

/**
 * Household
 *
 * @ORM\Table(name="household")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\HouseholdRepository")
 */
class Household
{

    /**
     * Mapping of possible household livelihood
     */
    const LIVELIHOOD = [
        0 => 'Agriculture - Livestock',
        1 => 'Agriculture - Crops',
        2 => 'Agriculture – Fishing',
        3 => 'Agriculture – Other',
        4 => 'Mining',
        5 => 'Construction',
        6 => 'Manufacturing',
        7 => 'Retail',
        8 => 'Transportation',
        9 => 'Education',
        10 => 'Health Care',
        11 => 'Hospitality and Tourism',
        12 => 'Legal Services',
        13 => 'Home Duties',
        14 => 'Religious Service',
        15 => 'IT and Telecommunications',
        16 => 'Finance and Insurance',
        17 => 'Manual Labour',
        18 => 'NGO and Non Profit',
        19 => 'Military or Police',
        20 => 'Government and Public Enterprise',
        21 => 'Garment Industry',
        22 => 'Security Industry',
        23 => 'Service Industry and Other Professionals',
        24 => 'Other'
    ];

    const ASSETS = [
        0 => 'A/C',
        1 => 'Agricultural Land',
        2 => 'Car',
        3 => 'Flatscreen TV',
        4 => 'Livestock',
        5 => 'Motorbike',
        6 => 'Washing Machine',
    ];

    const SHELTER_STATUSES = [
        0 => 'Values',
        1 => 'Tent',
        2 => 'Makeshift Shelter',
        3 => 'Transitional Shelter',
        4 => 'House/Apartment - Severely Damaged',
        5 => 'House/Apartment - Moderately Damaged',
        6 => 'House/Apartment - Good Condition',
        7 => 'Room or Space in Public Building',
        8 => 'Room or Space in Unfinished Building',
        9 => 'Other',
    ];

    const SUPPORT_RECIEVED_TYPES = [
        0 => 'MPCA',
        1 => 'Cash for Work',
        2 => 'Food Kit',
        3 => 'Food Voucher',
        4 => 'Hygiene Kit',
        5 => 'Shelter Kit',
        6 => 'Shelter Reconstruction Support',
        7 => 'Non Food Items',
        8 => 'Livelihoods Support',
        9 => 'Vocational Training',
        10 => 'None',
        11 => 'Other',
    ];

    /**
     * First value with a column in the csv which can move, depends on the number of country specifics
     * @var string
     */
    const firstColumnNonStatic = 'Q';

    /**
     * The row index of the header (with the name of country specifics)
     * @var int
     */
    const indexRowHeader = 1;

    /**
     * First row with data
     * @var int $first_row
     */
    const firstRow = 6;

    /**
     * Mapping between fields and CSV columns
     */
    const MAPPING_CSV = [
        // Household
        "address_street" => "A",
        "address_number" => "B",
        "address_postcode" => "C",
        "camp" => "D",
        "tent_number" => "E",
        "livelihood" => "F",
        "income_level" => "G",
        "food_consumption_score" => "H",
        "coping_strategies_index" => "I",
        "notes" => "J",
        "latitude" => "K",
        "longitude" => "L",
        "location" => [
            // Location
            "adm1" => "M",
            "adm2" => "N",
            "adm3" => "O",
            "adm4" => "P"
        ],
        // Beneficiary
        "beneficiaries" => [
            "local_given_name" => "Q",
            "local_family_name" => "R",
            "en_given_name" => "S",
            "en_family_name" => "T",
            "gender" => "U",
            "status" => "V",
            "residency_status" => "W",
            "date_of_birth" => "X",
            "vulnerability_criteria" => "Y",
            "phone1_type" => "Z",
            "phone1_prefix" => "AA",
            "phone1_number" => "AB",
            "phone1_proxy" => "AC",
            "phone2_type" => "AD",
            "phone2_prefix" => "AE",
            "phone2_number" => "AF",
            "phone2_proxy" => "AG",
            "national_id_type" => "AH",
            "national_id_number" => "AI",
        ]
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers"})
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="livelihood", type="integer", nullable=true)
     * @Groups({"FullHousehold"})
     */
    private $livelihood;

    /**
     * @var int[]
     *
     * @ORM\Column(name="assets", type="array", nullable=true)
     * @Groups({"FullHousehold"})
     */
    private $assets;

    /**
     * @var int
     *
     * @ORM\Column(name="shelter_status", type="integer", nullable=true)
     * @Groups({"FullHousehold"})
     */
    private $shelterStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="string", length=255, nullable=true)
     * @Groups({"FullHousehold"})
     */
    private $notes;

    /**
     * @var string
     *
     * @ORM\Column(name="latitude", type="string", length=45, nullable=true)
     * @Groups({"FullHousehold"})
     */
    private $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="string", length=45, nullable=true)
     * @Groups({"FullHousehold"})
     */
    private $longitude;

    /**
     * @var CountrySpecificAnswer
     *
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\CountrySpecificAnswer", mappedBy="household", cascade={"persist", "remove"})
     * @Groups({"FullHousehold"})
     */
    private $countrySpecificAnswers;

    /**
     * @var Beneficiary
     *
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\Beneficiary", mappedBy="household")
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers"})
     */
    private $beneficiaries;

    /**
     * @ORM\ManyToMany(targetEntity="ProjectBundle\Entity\Project", inversedBy="households")
     * @Groups({"FullHousehold", "SmallHousehold"})
     */
    private $projects;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $archived = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="incomeLevel", type="integer", nullable=true)
     * @Groups({"FullHousehold", "SmallHousehold"})
     */
    private $incomeLevel;

    /**
     * @var int
     *
     * @ORM\Column(name="foodConsumptionScore", type="integer", nullable=true)
     * @Groups({"FullHousehold", "SmallHousehold"})
     */
    private $foodConsumptionScore;

    /**
     * @var int
     *
     * @ORM\Column(name="copingStrategiesIndex", type="integer", nullable=true)
     * @Groups({"FullHousehold", "SmallHousehold"})
     */
    private $copingStrategiesIndex;

    /**
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\HouseholdLocation", mappedBy="household", cascade={"persist", "remove"})
     * @Groups({"FullHousehold", "SmallHousehold"})
     */
    private $householdLocations;

    /**
     * @var int
     *
     * @ORM\Column(name="debt_level", type="integer", nullable=true)
     * @Groups({"FullHousehold", "SmallHousehold"})
     */
    private $debtLevel;

    /**
     * @var int[]
     *
     * @ORM\Column(name="support_received_types", type="array", nullable=true)
     * @Groups({"FullHousehold", "SmallHousehold"})
     */
    private $supportReceivedTypes;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="support_date_received", type="date", nullable=true)
     * @JMS_Type("DateTime<'d-m-Y'>")
     * @Groups({"FullHousehold", "SmallHousehold"})
     */
    private $supportDateReceived;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->countrySpecificAnswers = new ArrayCollection();
        $this->beneficiaries = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->householdLocations = new ArrayCollection();

        $this->assets = [];
        $this->supportReceivedTypes = [];
    }


    /**
     * Set id.
     *
     * @param $id
     * @return Household
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set livelihood.
     *
     * @param int $livelihood
     *
     * @return Household
     */
    public function setLivelihood($livelihood)
    {
        $this->livelihood = $livelihood;

        return $this;
    }

    /**
     * Get livelihood.
     *
     * @return int
     */
    public function getLivelihood()
    {
        return $this->livelihood;
    }

    /**
     * @return int[]
     */
    public function getAssets(): array
    {
        return (array) $this->assets;
    }

    /**
     * @param int[] $assets
     *
     * @return self
     */
    public function setAssets($assets): self
    {
        foreach ((array) $assets as $asset) {
            if (!isset(self::ASSETS[$asset])) {
                throw new \InvalidArgumentException(sprintf('Argument 1 contain invalid asset key %d.', $asset));
            }
        }

        $this->assets = (array) $assets;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getShelterStatus(): ?int
    {
        return $this->shelterStatus;
    }

    /**
     * @param int|null $shelterStatus
     *
     * @return self
     */
    public function setShelterStatus(?int $shelterStatus): self
    {
        if (null !== $shelterStatus && !isset(self::SHELTER_STATUSES[$shelterStatus])) {
            throw new \InvalidArgumentException(sprintf('Argument 1 is not valid shelter status key.'));
        }

        $this->shelterStatus = $shelterStatus;

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
     * Set archived.
     *
     * @param bool $archived
     *
     * @return Household
     */
    public function setArchived($archived)
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Get archived.
     *
     * @return bool
     */
    public function getArchived()
    {
        return $this->archived;
    }

    /**
     * Set beneficiaries.
     *
     * @return Household
     */
    public function setBeneficiaries(\Doctrine\Common\Collections\Collection $collection = null)
    {
        $this->beneficiaries = $collection;

        return $this;
    }

    /**
     * Set project.
     *
     * @param \Doctrine\Common\Collections\Collection|null $collection
     * @return Household
     */
    public function setProjects(\Doctrine\Common\Collections\Collection $collection = null)
    {
        $this->projects = $collection;

        return $this;
    }

    /**
     * Set countrySpecificAnswer.
     *
     * @param \Doctrine\Common\Collections\Collection $collection
     *
     * @return Household
     */
    public function setCountrySpecificAnswers(\Doctrine\Common\Collections\Collection $collection = null)
    {
        $this->countrySpecificAnswers[] = $collection;

        return $this;
    }

    /**
     * Add countrySpecificAnswer.
     *
     * @param \BeneficiaryBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer
     *
     * @return Household
     */
    public function addCountrySpecificAnswer(\BeneficiaryBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer)
    {
        $this->countrySpecificAnswers[] = $countrySpecificAnswer;

        return $this;
    }

    /**
     * Remove countrySpecificAnswer.
     *
     * @param \BeneficiaryBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCountrySpecificAnswer(\BeneficiaryBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer)
    {
        return $this->countrySpecificAnswers->removeElement($countrySpecificAnswer);
    }

    /**
     * Get countrySpecificAnswers.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCountrySpecificAnswers()
    {
        return $this->countrySpecificAnswers;
    }

    /**
     * Add beneficiary.
     *
     * @param \BeneficiaryBundle\Entity\Beneficiary $beneficiary
     *
     * @return Household
     */
    public function addBeneficiary(\BeneficiaryBundle\Entity\Beneficiary $beneficiary)
    {
        $this->beneficiaries->add($beneficiary);

        return $this;
    }

    /**
     * Remove beneficiary.
     *
     * @param \BeneficiaryBundle\Entity\Beneficiary $beneficiary
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeBeneficiary(\BeneficiaryBundle\Entity\Beneficiary $beneficiary)
    {
        return $this->beneficiaries->removeElement($beneficiary);
    }

    /**
     * Get beneficiaries.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBeneficiaries()
    {
        return $this->beneficiaries;
    }

    /**
     * Reset the list of beneficiaries
     */
    public function resetBeneficiaries()
    {
        $this->beneficiaries = new \Doctrine\Common\Collections\ArrayCollection();

        return $this;
    }

    /**
     * Add project.
     *
     * @param \ProjectBundle\Entity\Project $project
     *
     * @return Household
     */
    public function addProject(\ProjectBundle\Entity\Project $project)
    {
        $this->projects[] = $project;
        return $this;
    }

    /**
     * Remove project.
     *
     * @param \ProjectBundle\Entity\Project $project
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeProject(\ProjectBundle\Entity\Project $project)
    {
        return $this->projects->removeElement($project);
    }

    /**
     * Get projects.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * @return int
     */
    public function getNumberDependents(): int
    {
        return count($this->getBeneficiaries()) - 1;
    }

    /**
     * Set incomeLevel.
     *
     * @param int $incomeLevel
     *
     * @return Household
     */
    public function setIncomeLevel($incomeLevel)
    {
        $this->incomeLevel = $incomeLevel;

        return $this;
    }

    /**
     * Get incomeLevel.
     *
     * @return int
     */
    public function getIncomeLevel()
    {
        return $this->incomeLevel;
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
     * @param \BeneficiaryBundle\Entity\HouseholdLocation $householdLocation
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeHouseholdLocation(\BeneficiaryBundle\Entity\HouseholdLocation $householdLocation)
    {
        return $this->householdLocations->removeElement($householdLocation);
    }

    /**
     * Add householdLocation.
     *
     * @param \BeneficiaryBundle\Entity\HouseholdLocation $householdLocation
     *
     * @return Household
     */
    public function addHouseholdLocation(\BeneficiaryBundle\Entity\HouseholdLocation $householdLocation)
    {
        $this->householdLocations[] = $householdLocation;
        $householdLocation->setHousehold($this);
        return $this;
    }

    /**
     * Get householdLocations.
     *
     * @return \Doctrine\Common\Collections\Collection
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
     * @return int[]
     */
    public function getSupportReceivedTypes(): array
    {
        return (array) $this->supportReceivedTypes;
    }

    /**
     * @param int[] $supportReceivedTypes
     *
     * @return self
     */
    public function setSupportReceivedTypes($supportReceivedTypes): self
    {
        foreach ((array) $supportReceivedTypes as $type) {
            if (!isset(self::SUPPORT_RECIEVED_TYPES[$type])) {
                throw new \InvalidArgumentException(sprintf('Argument 1 contain invalid received type key %d.', $type));
            }
        }

        $this->supportReceivedTypes = (array) $supportReceivedTypes;

        return $this;
    }


    /**
     * @return \DateTimeInterface|null
     */
    public function getSupportDateReceived(): ?\DateTimeInterface
    {
        return $this->supportDateReceived;
    }

    /**
     * @param \DateTimeInterface|null $supportDateReceived
     *
     * @return self
     */
    public function setSupportDateReceived(?\DateTimeInterface $supportDateReceived): self
    {
        $this->supportDateReceived = $supportDateReceived;

        return $this;
    }
}
