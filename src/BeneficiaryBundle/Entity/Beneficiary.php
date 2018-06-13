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
     * @var \DateTime
     *
     * @ORM\Column(name="dateOfBirth", type="datetime")
     * @JMS_Type("DateTime<'Y-m-d'>")
     */
    private $dateOfBirth;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="updatedAt", type="datetime", nullable=true)
     * @JMS_Type("DateTime<'Y-m-d H:m:i'>")
     */
    private $updatedAt;

    /**
     * @var BeneficiaryProfile
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\BeneficiaryProfile")
     */
    private $beneficiaryProfile;

    /**
     * @var VulnerabilityCriteria
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\VulnerabilityCriteria")
     */
    private $vulnerabilityCriteria;

    /**
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\HHMember", mappedBy="beneficiary")
     */
    private $hhMembers;

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
        $this->hhMembers = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set updatedAt.
     *
     * @param \DateTime|null $updatedAt
     *
     * @return Beneficiary
     */
    public function setUpdatedAt($updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return \DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set beneficiaryProfile.
     *
     * @param \BeneficiaryBundle\Entity\BeneficiaryProfile|null $beneficiaryProfile
     *
     * @return Beneficiary
     */
    public function setBeneficiaryProfile(\BeneficiaryBundle\Entity\BeneficiaryProfile $beneficiaryProfile = null)
    {
        $this->beneficiaryProfile = $beneficiaryProfile;

        return $this;
    }

    /**
     * Get beneficiaryProfile.
     *
     * @return \BeneficiaryBundle\Entity\BeneficiaryProfile|null
     */
    public function getBeneficiaryProfile()
    {
        return $this->beneficiaryProfile;
    }

    /**
     * Set vulnerabilityCriteria.
     *
     * @param \BeneficiaryBundle\Entity\VulnerabilityCriteria|null $vulnerabilityCriteria
     *
     * @return Beneficiary
     */
    public function setVulnerabilityCriteria(\BeneficiaryBundle\Entity\VulnerabilityCriteria $vulnerabilityCriteria = null)
    {
        $this->vulnerabilityCriteria = $vulnerabilityCriteria;

        return $this;
    }

    /**
     * Get vulnerabilityCriteria.
     *
     * @return \BeneficiaryBundle\Entity\VulnerabilityCriteria|null
     */
    public function getVulnerabilityCriteria()
    {
        return $this->vulnerabilityCriteria;
    }

    /**
     * Add hhMember.
     *
     * @param \BeneficiaryBundle\Entity\HHMember $hhMember
     *
     * @return Beneficiary
     */
    public function addHhMember(\BeneficiaryBundle\Entity\HHMember $hhMember)
    {
        $this->hhMembers[] = $hhMember;

        return $this;
    }

    /**
     * Remove hhMember.
     *
     * @param \BeneficiaryBundle\Entity\HHMember $hhMember
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeHhMember(\BeneficiaryBundle\Entity\HHMember $hhMember)
    {
        return $this->hhMembers->removeElement($hhMember);
    }

    /**
     * Get hhMembers.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHhMembers()
    {
        return $this->hhMembers;
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
}
