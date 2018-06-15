<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HHMember
 *
 * @ORM\Table(name="h_h_member")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\HHMemberRepository")
 */
class HHMember
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
     * @ORM\Column(name="gender", type="string", length=1)
     */
    private $gender;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateOfBirth", type="date")
     */
    private $dateOfBirth;

    /**
     * @var VulnerabilityCriteria
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\VulnerabilityCriteria")
     */
    private $vulnerabilityCriteria;

    /**
     * @var Beneficiary
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\VulnerabilityCriteria")
     */
    private $beneficiary;

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
     * Set gender.
     *
     * @param string $gender
     *
     * @return HHMember
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender.
     *
     * @return string
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
     * @return HHMember
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
     * Set vulnerabilityCriteria.
     *
     * @param \BeneficiaryBundle\Entity\VulnerabilityCriteria|null $vulnerabilityCriteria
     *
     * @return HHMember
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
     * Set beneficiary.
     *
     * @param \BeneficiaryBundle\Entity\VulnerabilityCriteria|null $beneficiary
     *
     * @return HHMember
     */
    public function setBeneficiary(\BeneficiaryBundle\Entity\VulnerabilityCriteria $beneficiary = null)
    {
        $this->beneficiary = $beneficiary;

        return $this;
    }

    /**
     * Get beneficiary.
     *
     * @return \BeneficiaryBundle\Entity\VulnerabilityCriteria|null
     */
    public function getBeneficiary()
    {
        return $this->beneficiary;
    }
}
