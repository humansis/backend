<?php

namespace NewApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AssistanceBeneficiary;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;


/**
 * GeneralReliefItem
 * @deprecated don't use, it is replaced by ReliefPackage
 * @see ReliefPackage
 *
 * @ORM\Table(name="general_relief_item")
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\GeneralReliefItemRepository")
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
     * @SymfonyGroups({"ValidatedAssistance"})
     */
    private $id;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="distributedAt", type="datetime", nullable=true)
     * @SymfonyGroups({"ValidatedAssistance"})
     */
    private $distributedAt;

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="string", length=255, nullable=true)
     *
     * @SymfonyGroups({"ValidatedAssistance"})
     */
    private $notes;
    
    /**
     * @var AssistanceBeneficiary
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\AssistanceBeneficiary", cascade={"persist"})
     * @ORM\JoinColumn(name="distribution_beneficiary_id")
     */
    private $assistanceBeneficiary;


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
     * @return AssistanceBeneficiary
     */
    public function getAssistanceBeneficiary()
    {
        return $this->assistanceBeneficiary;
    }
 
    /**
     * Set the value of Distribution Beneficiary
     *
     * @param AssistanceBeneficiary assistanceBeneficiary
     *
     * @return self
     */
    public function setAssistanceBeneficiary(AssistanceBeneficiary $assistanceBeneficiary)
    {
        $this->assistanceBeneficiary = $assistanceBeneficiary;
 
        return $this;
    }
}
