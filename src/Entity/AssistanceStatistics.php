<?php

declare(strict_types=1);

namespace Entity;

use Entity\Assistance;
use Doctrine\ORM\Mapping as ORM;

/**
 * Read only entity.
 *
 * @ORM\Entity(repositoryClass="Repository\AssistanceStatisticsRepository", readOnly=true)
 * @ORM\Table(name="view_assistance_statistics")
 */
class AssistanceStatistics
{
    /**
     * @ORM\Column(name="assistance_id", type="integer")
     * @ORM\Id
     */
    private ?int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Assistance")
     */
    private \Entity\Assistance $assistance;

    /**
     * @ORM\Column(name="number_of_beneficiaries", type="integer")
     */
    private int $numberOfBeneficiaries;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private float $amountTotal;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private float $amountDistributed;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private ?float $amountUsed = null;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private ?float $amountSent = null;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private ?float $amountPickedUp = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getNumberOfBeneficiaries(): int
    {
        return $this->numberOfBeneficiaries;
    }

    public function getAmountTotal(): ?float
    {
        return null === $this->amountTotal ? null : (float) $this->amountTotal;
    }

    public function getAmountDistributed(): ?float
    {
        return null === $this->amountDistributed ? null : (float) $this->amountDistributed;
    }

    public function getAmountUsed(): ?float
    {
        return null === $this->amountUsed ? null : (float) $this->amountUsed;
    }

    public function getAmountSent(): ?float
    {
        return null === $this->amountSent ? null : (float) $this->amountSent;
    }

    public function getAmountPickedUp(): ?float
    {
        return null === $this->amountPickedUp ? null : (float) $this->amountPickedUp;
    }
}
