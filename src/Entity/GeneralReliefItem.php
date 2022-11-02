<?php

namespace Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Entity\AssistanceBeneficiary;
use Entity\Assistance\ReliefPackage;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * GeneralReliefItem
 *
 * @deprecated don't use, it is replaced by ReliefPackage
 * @see ReliefPackage
 *
 * @ORM\Table(name="general_relief_item")
 * @ORM\Entity(repositoryClass="Repository\GeneralReliefItemRepository")
 */
class GeneralReliefItem
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    #[SymfonyGroups(['ValidatedAssistance'])]
    private $id;

    /**
     * @ORM\Column(name="distributedAt", type="datetime", nullable=true)
     */
    #[SymfonyGroups(['ValidatedAssistance'])]
    private ?\DateTime $distributedAt = null;

    /**
     *
     * @ORM\Column(name="notes", type="string", length=255, nullable=true)
     *
     */
    #[SymfonyGroups(['ValidatedAssistance'])]
    private ?string $notes = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Entity\AssistanceBeneficiary", cascade={"persist"})
     * @ORM\JoinColumn(name="distribution_beneficiary_id")
     */
    private ?\Entity\AssistanceBeneficiary $assistanceBeneficiary = null;

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
     * @param DateTime|null $distributedAt
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
     * @return DateTime|null
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
