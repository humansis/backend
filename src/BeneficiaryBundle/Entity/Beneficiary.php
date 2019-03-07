<?php

namespace BeneficiaryBundle\Entity;

use DistributionBundle\Entity\DistributionBeneficiary;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Type as JMS_Type;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use CommonBundle\Utils\ExportableInterface;


/**
 * Beneficiary
 *
 * @ORM\Table(name="beneficiary")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\BeneficiaryRepository")
 */
class Beneficiary implements ExportableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="givenName", type="string", length=255, nullable=true)
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution"})
     * @Assert\NotBlank(message="The given name is required.")
     */
    private $givenName;

    /**
     * @var string
     *
     * @ORM\Column(name="familyName", type="string", length=255, nullable=true)
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution"})
     * @Assert\NotBlank(message="The family name is required.")
     */
    private $familyName;

    /**
     * @var int
     *
     * @ORM\Column(name="gender", type="smallint")
     * @Groups({"FullHousehold", "FullReceivers", "ValidatedDistribution"})
     * @Assert\NotBlank(message="The gender is required.")
     */
    private $gender;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean")
     * @Groups({"FullHousehold", "FullReceivers", "ValidatedDistribution", "SmallHousehold"})
     * @Assert\NotBlank(message="The status is required.")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="residency_status", type="string", length=20)
     * @Groups({"FullHousehold", "FullReceivers", "ValidatedDistribution", "SmallHousehold"})
     * @Assert\Regex("/^(refugee|idp|resident)$/i")
     */
    private $residencyStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateOfBirth", type="date")
     * @JMS_Type("DateTime<'Y-m-d'>")
     * @Groups({"FullHousehold", "FullReceivers", "ValidatedDistribution"})
     * @Assert\NotBlank(message="The date of birth is required.")
     */
    private $dateOfBirth;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     * @JMS_Type("DateTime<'Y-m-d H:m:i'>")
     * @Groups({"FullHousehold"})
     */
    private $updatedOn;

    /**
     * @ORM\OneToOne(targetEntity="BeneficiaryBundle\Entity\Profile", cascade={"persist", "remove"})
     * @Groups({"FullHousehold"})
     */
    private $profile;

    /**
     * @var Household
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Household", inversedBy="beneficiaries")
     */
    private $household;

    /**
     * @var VulnerabilityCriterion
     *
     * @ORM\ManyToMany(targetEntity="BeneficiaryBundle\Entity\VulnerabilityCriterion", cascade={"persist"})
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution"})
     */
    private $vulnerabilityCriteria;

    /**
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\Phone", mappedBy="beneficiary", cascade={"persist", "remove"})
     * @Groups({"FullHousehold", "FullReceivers", "ValidatedDistribution"})
     */
    private $phones;

    /**
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\NationalId", mappedBy="beneficiary", cascade={"persist", "remove"})
     * @Groups({"FullHousehold", "FullReceivers"})
     */
    private $nationalIds;

    /**
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\DistributionBeneficiary", mappedBy="beneficiary", cascade={"remove"})
     * @Groups({"FullReceivers"})
     *
     * @var DistributionBeneficiary $distributionBeneficiary
     */
    private $distributionBeneficiary;



    /**
     * Constructor
     */
    public function __construct()
    {
        $this->vulnerabilityCriteria = new \Doctrine\Common\Collections\ArrayCollection();
        $this->phones = new \Doctrine\Common\Collections\ArrayCollection();
        $this->nationalIds = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setUpdatedOn(new \DateTime());

        //TODO check if updatedOn everytime
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
     * Set givenName.
     *
     * @param string $givenName
     *
     * @return Beneficiary
     */
    public function setGivenName($givenName)
    {
        $this->givenName = $givenName;

        return $this;
    }

    /**
     * Get givenName.
     *
     * @return string
     */
    public function getGivenName()
    {
        return $this->givenName;
    }

    /**
     * Set familyName.
     *
     * @param string $familyName
     *
     * @return Beneficiary
     */
    public function setFamilyName($familyName)
    {
        $this->familyName = $familyName;

        return $this;
    }

    /**
     * Get familyName.
     *
     * @return string
     */
    public function getFamilyName()
    {
        return $this->familyName;
    }

    /**
     * Set gender.
     *
     * @param int $gender
     *
     * @return Beneficiary
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender.
     *
     * @return int
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set dateOfBirth.
     *
     * @param \DateTime $dateOfBirth
     *
     * @return Beneficiary
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    /**
     * Get dateOfBirth.
     *
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * Set updatedOn.
     *
     * @param \DateTime|null $updatedOn
     *
     * @return Beneficiary
     */
    public function setUpdatedOn($updatedOn = null)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get updatedOn.
     *
     * @return \DateTime|null
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * Set household.
     *
     * @param \BeneficiaryBundle\Entity\Household|null $household
     *
     * @return Beneficiary
     */
    public function setHousehold(\BeneficiaryBundle\Entity\Household $household = null)
    {
        $this->household = $household;

        return $this;
    }

    /**
     * Get household.
     *
     * @return \BeneficiaryBundle\Entity\Household|null
     */
    public function getHousehold()
    {
        return $this->household;
    }

    /**
     * Add vulnerabilityCriterion.
     *
     * @param \BeneficiaryBundle\Entity\VulnerabilityCriterion $vulnerabilityCriterion
     *
     * @return Beneficiary
     */
    public function addVulnerabilityCriterion(\BeneficiaryBundle\Entity\VulnerabilityCriterion $vulnerabilityCriterion)
    {
        $this->vulnerabilityCriteria[] = $vulnerabilityCriterion;

        return $this;
    }

    /**
     * Remove vulnerabilityCriterion.
     *
     * @param \BeneficiaryBundle\Entity\VulnerabilityCriterion $vulnerabilityCriterion
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeVulnerabilityCriterion(\BeneficiaryBundle\Entity\VulnerabilityCriterion $vulnerabilityCriterion)
    {
        return $this->vulnerabilityCriteria->removeElement($vulnerabilityCriterion);
    }

    /**
     * Get vulnerabilityCriterion.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVulnerabilityCriteria()
    {
        return $this->vulnerabilityCriteria;
    }

    /**
     * Set VulnerabilityCriterions.
     *
     * @return Beneficiary
     */
    public function setVulnerabilityCriteria(\Doctrine\Common\Collections\Collection $collection = null)
    {
        $this->vulnerabilityCriteria = $collection;

        return $this;
    }

    /**
     * Add phone.
     *
     * @param \BeneficiaryBundle\Entity\Phone $phone
     *
     * @return Beneficiary
     */
    public function addPhone(\BeneficiaryBundle\Entity\Phone $phone)
    {
        $this->phones[] = $phone;

        return $this;
    }

    /**
     * Remove phone.
     *
     * @param \BeneficiaryBundle\Entity\Phone $phone
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePhone(\BeneficiaryBundle\Entity\Phone $phone)
    {
        return $this->phones->removeElement($phone);
    }

    /**
     * Get phones.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPhones()
    {
        return $this->phones;
    }

    /**
     * Set phones.
     *
     * @param $collection
     *
     * @return Beneficiary
     */
    public function setPhones(\Doctrine\Common\Collections\Collection $collection = null)
    {
        $this->phones = $collection;

        return $this;
    }

    /**
     * Set nationalId.
     *
     * @param  $collection
     *
     * @return Beneficiary
     */
    public function setNationalIds(\Doctrine\Common\Collections\Collection $collection = null)
    {
        $this->nationalIds = $collection;

        return $this;
    }

    /**
     * Add nationalId.
     *
     * @param \BeneficiaryBundle\Entity\NationalId $nationalId
     *
     * @return Beneficiary
     */
    public function addNationalId(\BeneficiaryBundle\Entity\NationalId $nationalId)
    {
        $this->nationalIds[] = $nationalId;

        return $this;
    }

    /**
     * Remove nationalId.
     *
     * @param \BeneficiaryBundle\Entity\NationalId $nationalId
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeNationalId(\BeneficiaryBundle\Entity\NationalId $nationalId)
    {
        return $this->nationalIds->removeElement($nationalId);
    }

    /**
     * Get nationalIds.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNationalIds()
    {
        return $this->nationalIds;
    }

    /**
     * Set status.
     *
     * @param bool $status
     *
     * @return Beneficiary
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getResidencyStatus()
    {
        return $this->residencyStatus;
    }

    /**
     * @param string $residencyStatus
     *
     * @return Beneficiary
     */
    public function setResidencyStatus($residencyStatus)
    {
        $this->residencyStatus = $residencyStatus;
        return $this;
    }

    /**
     * Set profile.
     *
     * @param \BeneficiaryBundle\Entity\Profile|null $profile
     *
     * @return Beneficiary
     */
    public function setProfile(\BeneficiaryBundle\Entity\Profile $profile = null)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * Get profile.
     *
     * @return \BeneficiaryBundle\Entity\Profile|null
     */
    public function getProfile()
    {
        return $this->profile;
    }


    /**
     * Returns an array representation of this class in order to prepare the export
     * @return array
     */
    function getMappedValueForExport(): array
    {
        // Recover the phones of the beneficiary
        $typephones = ["",""];
        $prefixphones = ["",""];
        $valuesphones = ["",""];
        $proxyphones = ["",""];

        $index = 0;
        foreach ($this->getPhones()->getValues() as $value) {
            $typephones[$index] = $value->getType();
            $prefixphones[$index] = $value->getPrefix();
            $valuesphones[$index] = $value->getNumber();
            $proxyphones[$index] = $value->getProxy();
            $index++;
        }

        // Recover the  criterions from Vulnerability criteria object
        $valuescriteria = [];
        foreach ($this->getVulnerabilityCriteria()->getValues() as $value) {
            array_push($valuescriteria, $value->getFieldString());
        }
        $valuescriteria = join(',', $valuescriteria);

        // Recover nationalID from nationalID object
        $typenationalID = [];
        $valuesnationalID = [];
        foreach ($this->getNationalIds()->getValues() as $value) {
            array_push($typenationalID, $value->getIdType());
            array_push($valuesnationalID, $value->getIdNumber());
        }
        $typenationalID = join(',', $typenationalID);
        $valuesnationalID = join(',',$valuesnationalID);

        //Recover country specifics for the household
        $valueCountrySpecific = [];
        foreach ($this->getHousehold()->getCountrySpecificAnswers()->getValues() as $value){
            $valueCountrySpecific[$value->getCountrySpecific()->getFieldString()] = $value->getAnswer();
        }

        if ($this->getGender() == 0)
            $valueGender = "Female";
        else
            $valueGender = "Male";

        // Recover adm1 , adm2 , adm3 , adm 4 from localisation object : we have to verify if they are null before to get the name
        $adm1 = ( ! empty($this->getHousehold()->getLocation()->getAdm1()) ) ? $this->getHousehold()->getLocation()->getAdm1()->getName() : '';
        $adm2 = ( ! empty($this->getHousehold()->getLocation()->getAdm2()) ) ? $this->getHousehold()->getLocation()->getAdm2()->getName() : '';
        $adm3 = ( ! empty($this->getHousehold()->getLocation()->getAdm3()) ) ? $this->getHousehold()->getLocation()->getAdm3()->getName() : '';
        $adm4 = ( ! empty($this->getHousehold()->getLocation()->getAdm4()) ) ? $this->getHousehold()->getLocation()->getAdm4()->getName() : '';

        if ($this->status === true) {
            $finalArray = [
                "addressStreet" => $this->getHousehold()->getAddressStreet(),
                "addressNumber" => $this->getHousehold()->getAddressNumber(),
                "addressPostcode" => $this->getHousehold()->getAddressPostcode(),
                "livelihood" => $this->getHousehold()->getLivelihood(),
                "notes" => $this->getHousehold()->getNotes(),
                "latitude" => $this->getHousehold()->getLatitude(),
                "longitude" => $this->getHousehold()->getLongitude(),
                "adm1" => $adm1,
                "adm2" =>$adm2,
                "adm3" =>$adm3,
                "adm4" =>$adm4,
            ];
        } else {
            $finalArray = [
                "addressStreet" => "",
                "addressNumber" => "",
                "addressPostcode" => "",
                "livelihood" => "",
                "notes" => "",
                "latitude" => "",
                "longitude" => "",
                "adm1" => "",
                "adm2" => "",
                "adm3" => "",
                "adm4" => "",
            ];
        }

        $tempBenef = [ "givenName" => $this->getGivenName(),
            "familyName"=> $this->getFamilyName(),
            "gender" => $valueGender,
            "status" => $this->getStatus(),
            "residencyStatus" => $this->getResidencyStatus(),
            "dateOfBirth" => $this->getDateOfBirth()->format('Y-m-d'),
            "vulnerabilityCriteria" => $valuescriteria,
            "type phone 1" => $typephones[0],
            "prefix phone 1" => $prefixphones[0],
            "phones 1" => $valuesphones[0],
            "proxy phone 1" => $proxyphones[0],
            "type phone 2" => $typephones[1],
            "prefix phone 2" => $prefixphones[1],
            "phones 2" => $valuesphones[1],
            "proxy phone 2" => $proxyphones[1],
            "type national ID" => $typenationalID,
            "nationalIds" => $valuesnationalID
        ];

        foreach ($valueCountrySpecific as $key => $value)
            $finalArray[$key] = $value;

        foreach ($tempBenef as $key => $value)
            $finalArray[$key] = $value;

        return $finalArray;
    }
}
