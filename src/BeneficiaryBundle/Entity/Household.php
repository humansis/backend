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
     * First value with a column in the csv which can move, depends on the number of country specifics
     * @var string
     */
    const firstColumnNonStatic = 'L';

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
        "notes" => "E",
        "latitude" => "F",
        "longitude" => "G",
        "location" => [
            // Location
            "adm1" => "H",
            "adm2" => "I",
            "adm3" => "J",
            "adm4" => "K"
        ],
        // Beneficiary
        "beneficiaries" => [
            "given_name" => "L",
            "family_name" => "M",
            "gender" => "N",
            "status" => "O",
            "residency_status" => "P",
            "date_of_birth" => "Q",
            "vulnerability_criteria" => "R",
            "phone1_type" => "S",
            "phone1_prefix" => "T",
            "phone1_number" => "U",
            "phone1_proxy" => "V",
            "phone2_type" => "W",
            "phone2_prefix" => "X",
            "phone2_number" => "Y",
            "phone2_proxy" => "Z",
            "national_id_type" => "AA",
            "national_id_number" => "AB"
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
        // $project->setNumberOfHouseholds($project->getNumberOfHouseholds() + 1);
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
        $project->setNumberOfHouseholds($project->getNumberOfHouseholds() + 1);
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
        $project->setNumberOfHouseholds($project->getNumberOfHouseholds() - 1);
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


}
