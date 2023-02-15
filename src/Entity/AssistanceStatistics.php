<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Read only entity.
 */
#[ORM\Table(name: 'view_assistance_statistics')]
#[ORM\Entity(repositoryClass: 'Repository\AssistanceStatisticsRepository', readOnly: true)]
class AssistanceStatistics
{
    #[ORM\Column(name: 'assistance_id', type: 'integer')]
    #[ORM\Id]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: 'Entity\Assistance')]
    private \Entity\Assistance $assistance;

    #[ORM\Column(name: 'number_of_beneficiaries', type: 'integer')]
    private int $numberOfBeneficiaries;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private float | null $amountTotal;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private float | null $amountDistributed;

    #[ORM\Column(name: 'beneficiaries_deleted', type: 'integer')]
    private int $beneficiariesDeleted;

    #[ORM\Column(name: 'beneficiaries_reached', type: 'integer')]
    private int $beneficiariesReached;

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

    public function getBeneficiariesDeleted(): int
    {
        return $this->beneficiariesDeleted;
    }

    public function getBeneficiariesReached(): int
    {
        return $this->beneficiariesReached;
    }
}
