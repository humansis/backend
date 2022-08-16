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
     * @var string
     *
     * @ORM\Column(type="string")
     * @ORM\Id
     */
    private $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Entity\Project")
     */
    private $project;

    /**
     * @var Beneficiary
     *
     * @ORM\ManyToOne(targetEntity="Entity\Beneficiary")
     */
    private $beneficiary;

    /**
     * @var Assistance
     *
     * @ORM\ManyToOne(targetEntity="Entity\Assistance")
     */
    private $assistance;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="Entity\Location")
     */
    private $location;

    /**
     * @var string
     *
     * @ORM\Column(name="bnf_type", type="string")
     */
    private $beneficiaryType;

    /**
     * @var Commodity
     *
     * @ORM\ManyToOne(targetEntity="Entity\Commodity")
     */
    private $commodity;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $amount;

    /**
     * @var float|null
     *
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $spent;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $modalityType;

    /**
     * @var DateTimeInterface|null
     *
     * @ORM\Column(name="date_distribution", type="datetime", nullable=true)
     */
    private $dateDistribution;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $carrierNumber;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="Entity\User")
     */
    private $fieldOfficer;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return Beneficiary
     */
    public function getBeneficiary(): Beneficiary
    {
        return $this->beneficiary;
    }

    /**
     * @return Assistance
     */
    public function getAssistance(): Assistance
    {
        return $this->assistance;
    }

    /**
     * @return Location
     */
    public function getLocation(): Location
    {
        return $this->location;
    }

    /**
     * @return string
     */
    public function getBeneficiaryType(): string
    {
        return $this->beneficiaryType;
    }

    /**
     * @return Commodity
     */
    public function getCommodity(): Commodity
    {
        return $this->commodity;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return (float) $this->amount;
    }

    /**
     * @return float|null
     */
    public function getSpent(): ?float
    {
        return $this->spent !== null
            ? (float) $this->spent
            : null;
    }

    /**
     * @return string
     */
    public function getModalityType(): string
    {
        return $this->modalityType;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getDateDistribution(): ?DateTimeInterface
    {
        return $this->dateDistribution;
    }

    /**
     * @return string|null
     */
    public function getCarrierNumber(): ?string
    {
        return $this->carrierNumber;
    }

    /**
     * @return User|null
     */
    public function getFieldOfficer(): ?User
    {
        return $this->fieldOfficer;
    }
}
