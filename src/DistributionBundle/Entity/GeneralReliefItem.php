<?php

namespace DistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DistributionBundle\Entity\DistributionBeneficiary;
use JMS\Serializer\Annotation\Groups;

/**
 * GeneralReliefItem
 *
 * @ORM\Table(name="general_relief_item")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\GeneralReliefItemRepository")
 */
class GeneralReliefItem
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"ValidatedDistribution"})
     */
    private $id;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="distributedAt", type="datetime", nullable=true)
     *
     * @Groups({"ValidatedDistribution"})
     */
    private $distributedAt;

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="string", length=255, nullable=true)
     *
     * @Groups({"ValidatedDistribution"})
     */
    private $notes;
    
    /**
     * @var DistributionBeneficiary
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\DistributionBeneficiary", inversedBy="generalReliefs", cascade={"persist"})
     */
    private $distributionBeneficiary;


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
     * Set distributedAt.
     *
     * @param \DateTime|null $distributedAt
     *
     * @return GeneralReliefItem
     */
    public function setDistributedAt($distributedAt = null)
    {
        $this->distributedAt = $distributedAt;

        return $this;
    }

    /**
     * Get distributedAt.
     *
     * @return \DateTime|null
     */
    public function getDistributedAt()
    {
        return $this->distributedAt;
    }

    /**
     * Set notes.
     *
     * @param string|null $notes
     *
     * @return GeneralReliefItem
     */
    public function setNotes($notes = null)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes.
     *
     * @return string|null
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /** 
     * Set the value of Id 
     * 
     * @param int id
     * 
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;
 
        return $this;
    }
 
    /**
     * Get the value of Distribution Beneficiary 
     * 
     * @return DistributionBeneficiary
     */
    public function getDistributionBeneficiary()
    {
        return $this->distributionBeneficiary;
    }
 
    /** 
     * Set the value of Distribution Beneficiary 
     * 
     * @param DistributionBeneficiary distributionBeneficiary
     * 
     * @return self
     */
    public function setDistributionBeneficiary(DistributionBeneficiary $distributionBeneficiary)
    {
        $this->distributionBeneficiary = $distributionBeneficiary;
 
        return $this;
    }
 
}
