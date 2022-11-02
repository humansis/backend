<?php

declare(strict_types=1);

namespace Entity;

use DateTimeInterface;
use Entity\Beneficiary;
use Entity\Location;
use Entity\Assistance;
use Entity\Commodity;
use Doctrine\ORM\Mapping as ORM;
use Entity\Project;
use Entity\User;

/**
 * Read only entity.
 *
 * @ORM\MappedSuperclass(repositoryClass="Repository\DistributedItemRepository")
 * @ORM\Table(name="view_distributed_item")
 */
class DistributedItem
{
    /**
     *
     * @ORM\Column(type="string")
     * @ORM\Id
     */
    private string $id;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Project")
     */
    private \Entity\Project $project;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Beneficiary")
     */
    private \Entity\Beneficiary $beneficiary;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Assistance")
     */
    private \Entity\Assistance $assistance;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Location")
     */
    private \Entity\Location $location;

    /**
     * @ORM\Column(name="bnf_type", type="string")
     */
    private string $beneficiaryType;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Commodity")
     */
    private \Entity\Commodity $commodity;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private float $amount;

    /**
     *
     * controlled by database triggers on smartcard_payment_record table
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private ?float $spent = null;

    /**
     * @ORM\Column(type="string")
     */
    private string $modalityType;

    /**
     * @ORM\Column(name="date_distribution", type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $dateDistribution = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $carrierNumber = null;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\User")
     */
    private ?\Entity\User $fieldOfficer = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getBeneficiary(): Beneficiary
    {
        return $this->beneficiary;
    }

    public function getAssistance(): Assistance
    {
        return $this->assistance;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getBeneficiaryType(): string
    {
        return $this->beneficiaryType;
    }

    public function getCommodity(): Commodity
    {
        return $this->commodity;
    }

    public function getAmount(): float
    {
        return (float) $this->amount;
    }

    public function getSpent(): ?float
    {
        return $this->spent !== null
            ? (float) $this->spent
            : null;
    }

    public function getModalityType(): string
    {
        return $this->modalityType;
    }

    public function getDateDistribution(): ?DateTimeInterface
    {
        return $this->dateDistribution;
    }

    public function getCarrierNumber(): ?string
    {
        return $this->carrierNumber;
    }

    public function getFieldOfficer(): ?User
    {
        return $this->fieldOfficer;
    }
}
