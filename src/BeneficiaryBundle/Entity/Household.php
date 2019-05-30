<?php

namespace BeneficiaryBundle\Entity;

use CommonBundle\Entity\Location;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

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

    /**
     * First value with a column in the csv which can move, depends on the number of country specifics
     * @var string
     */
    const firstColumnNonStatic = 'M';

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
        "livelihood" => "D",
        "income_level" => "E",
        "notes" => "F",
        "latitude" => "G",
        "longitude" => "H",
        "location" => [
            // Location
            "adm1" => "I",
            "adm2" => "J",
            "adm3" => "K",
            "adm4" => "L"
        ],
        // Beneficiary
        "beneficiaries" => [
            "local_given_name" => "M",
            "local_family_name" => "N",
            "en_given_name" => "O",
            "en_family_name" => "P",
            "gender" => "Q",
            "status" => "R",
            "residency_status" => "S",
            "date_of_birth" => "T",
            "vulnerability_criteria" => "U",
            "phone1_type" => "V",
            "phone1_prefix" => "W",
            "phone1_number" => "X",
            "phone1_proxy" => "Y",
            "phone2_type" => "Z",
            "phone2_prefix" => "AA",
            "phone2_number" => "AB",
            "phone2_proxy" => "AC",
            "national_id_type" => "AD",
            "national_id_number" => "AE"
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
     * @var string
     *
     * @ORM\Column(name="address_street", type="string", length=255, nullable=true)
     * @Groups({"FullHousehold", "FullReceivers"})
     */
    private $addressStreet;

    /**
     * @var string
     *
     * @ORM\Column(name="address_number", type="string", length=255, nullable=true)
     * @Groups({"FullHousehold", "FullReceivers"})
     */
    private $addressNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="address_postcode", type="string", length=255, nullable=true)
     * @Groups({"FullHousehold", "FullReceivers"})
     */
    private $addressPostcode;

    /**
     * @var int
     *
     * @ORM\Column(name="livelihood", type="integer", nullable=true)
     * @Groups({"FullHousehold"})
     */
    private $livelihood;

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
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="CommonBundle\Entity\Location", cascade={"persist"})
     * @Groups({"FullHousehold", "SmallHousehold"})
     */
    private $location;

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
     * Number of dependent beneficiaries
     * @var int
     * @Groups({"SmallHousehold"})
     */
    private $numberDependents;

    /**
     * @var int
     *
     * @ORM\Column(name="incomeLevel", type="integer", nullable=true)
     * @Groups({"FullHousehold", "SmallHousehold"})
     */
    private $incomeLevel;

    /**
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\HouseholdLocation", mappedBy="household", cascade={"persist", "remove"})
     * @Groups({"FullHousehold", "SmallHousehold"})
     */
    private $householdLocations;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->countrySpecificAnswers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->beneficiaries = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set addressStreet.
     *
     * @param string $addressStreet
     *
     * @return Household
     */
    public function setAddressStreet($addressStreet)
    {
        $this->addressStreet = $addressStreet;

        return $this;
    }

    /**
     * Get addressStreet.
     *
     * @return string
     */
    public function getAddressStreet()
    {
        return $this->addressStreet;
    }

    /**
     * Set addressNumber.
     *
     * @param string $addressNumber
     *
     * @return Household
     */
    public function setAddressNumber($addressNumber)
    {
        $this->addressNumber = $addressNumber;

        return $this;
    }

    /**
     * Get addressNumber.
     *
     * @return string
     */
    public function getAddressNumber()
    {
        return $this->addressNumber;
    }

    /**
     * Set addressPostcode.
     *
     * @param string $addressPostcode
     *
     * @return Household
     */
    public function setAddressPostcode($addressPostcode)
    {
        $this->addressPostcode = $addressPostcode;

        return $this;
    }

    /**
     * Get addressPostcode.
     *
     * @return string
     */
    public function getAddressPostcode()
    {
        return $this->addressPostcode;
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
     * Set location.
     *
     * @param \CommonBundle\Entity\Location|null $location
     *
     * @return Household
     */
    public function setLocation(\CommonBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return \CommonBundle\Entity\Location|null
     */
    public function getLocation()
    {
        return $this->location;
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
        return $this->numberDependents;
    }

    /**
     * @param int $numberDependents
     * @return Household
     */
    public function setNumberDependents(int $numberDependents)
    {
        $this->numberDependents = $numberDependents;

        return $this;
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
     * Get householdLocations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHouseholdLocations()
    {
        return $this->householdLocations;
    }

}
