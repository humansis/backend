<?php

namespace DistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;


/**
 * GeneralReliefItem
 * @deprecated don't use, it is replaced by ReliefPackage
 * @see ReliefPackage
 *
 * @ORM\Table(name="general_relief_item")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\GeneralReliefItemRepository")
 */
class GeneralReliefItem extends AbstractEntity
{
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
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\AssistanceBeneficiary", cascade={"persist"})
     * @ORM\JoinColumn(name="distribution_beneficiary_id")
     */
    private $assistanceBeneficiary;


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
