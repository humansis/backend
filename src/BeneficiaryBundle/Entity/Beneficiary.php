<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
     */
    private $dateOfBirth;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="updatedAt", type="datetime", nullable=true)
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
}
