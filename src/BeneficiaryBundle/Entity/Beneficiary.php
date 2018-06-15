<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Type as JMS_Type;

/**
 * Beneficiary
 *
 * @ORM\Table(name="beneficiary")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\BeneficiaryRepository")
 */
class Beneficiary
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="givenName", type="string", length=255)
     */
    private $givenName;

    /**
     * @var string
     *
     * @ORM\Column(name="familyName", type="string", length=255)
     */
    private $familyName;

    /**
     * @var int
     *
     * @ORM\Column(name="gender", type="smallint")
     */
    private $gender;

    /**
     * @var boolean
     *
     * @ORM\Column(name="household_head", type="boolean")
     */
    private $householdHead;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateOfBirth", type="datetime")
     * @JMS_Type("DateTime<'Y-m-d'>")
     */
    private $dateOfBirth;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     * @JMS_Type("DateTime<'Y-m-d H:m:i'>")
     */
    private $updatedOn;

    /**
     * @ORM\ManyToMany(targetEntity="BeneficiaryBundle\Entity\Profile")
     */
    private $profile;

    /**
     * @var Household
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Household")
     */
    private $household;

    /**
     * @var VulnerabilityCriterion
     *
     * @ORM\ManyToMany(targetEntity="BeneficiaryBundle\Entity\VulnerabilityCriterion")
     */
    private $vulnerabilityCriterions;

    /**
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\Phone", mappedBy="beneficiary")
     */
    private $phones;

    /**
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\NationalId", mappedBy="beneficiary")
     */
    private $nationalIds;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->vulnerabilityCriterions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->phones = new \Doctrine\Common\Collections\ArrayCollection();
        $this->nationalIds = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set householdHead.
     *
     * @param bool $householdHead
     *
     * @return Beneficiary
     */
    public function setHouseholdHead($householdHead)
    {
        $this->householdHead = $householdHead;

        return $this;
    }

    /**
     * Get householdHead.
     *
     * @return bool
     */
    public function getHouseholdHead()
    {
        return $this->householdHead;
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
        $this->vulnerabilityCriterions[] = $vulnerabilityCriterion;

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
        return $this->vulnerabilityCriterions->removeElement($vulnerabilityCriterion);
    }

    /**
     * Get vulnerabilityCriterions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVulnerabilityCriterions()
    {
        return $this->vulnerabilityCriterions;
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
     * Add profile.
     *
     * @param \BeneficiaryBundle\Entity\Profile $profile
     *
     * @return Beneficiary
     */
    public function addProfile(\BeneficiaryBundle\Entity\Profile $profile)
    {
        $this->profile[] = $profile;

        return $this;
    }

    /**
     * Remove profile.
     *
     * @param \BeneficiaryBundle\Entity\Profile $profile
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeProfile(\BeneficiaryBundle\Entity\Profile $profile)
    {
        return $this->profile->removeElement($profile);
    }

    /**
     * Get profile.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProfile()
    {
        return $this->profile;
    }
}
