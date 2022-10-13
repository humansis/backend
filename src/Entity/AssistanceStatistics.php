<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Read only entity.
 *
 * @ORM\MappedSuperclass(repositoryClass="Repository\AssistanceStatisticsRepository")
 * @ORM\Table(name="view_assistance_statistics")
 */
class AssistanceStatistics
{
    /**
     * @var int
     * @ORM\Column(name="assistance_id", type="integer")
     * @ORM\Id
     */
    private $id;

    /**
     * @var Assistance
     * @ORM\ManyToOne(targetEntity="Entity\Assistance")
     */
    private $assistance;

    /**
     * @var int
     * @ORM\Column(name="number_of_beneficiaries", type="integer")
     */
    private $numberOfBeneficiaries;

    /**
     * @var float
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $amountTotal;

    /**
     * @var float
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $amountDistributed;

    /**
     * @var int
     * @ORM\Column(name="beneficiaries_deleted", type="integer")
     */
    private $beneficiariesDeleted;

    /**
     * @var int
     * @ORM\Column(name="beneficiaries_reached", type="integer")
     */
    private $beneficiariesReached;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getNumberOfBeneficiaries(): int
    {
        return $this->numberOfBeneficiaries;
    }

    /**
     * @return float|null
     */
    public function getAmountTotal(): ?float
    {
        return null === $this->amountTotal ? null : (float) $this->amountTotal;
    }

    /**
     * @return float|null
     */
    public function getAmountDistributed(): ?float
    {
        return null === $this->amountDistributed ? null : (float) $this->amountDistributed;
    }

    /**
     * @return int
     */
    public function getBeneficiariesDeleted(): int
    {
        return $this->beneficiariesDeleted;
    }

    /**
     * @return int
     */
    public function getBeneficiariesReached(): int
    {
        return $this->beneficiariesReached;
    }
}
