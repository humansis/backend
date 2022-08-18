<?php

declare(strict_types=1);

namespace Entity\Assistance;

use DateTimeInterface;
use Entity\AssistanceBeneficiary;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Entity\Helper\CreatedAt;
use Entity\Helper\LastModifiedAt;
use Entity\Helper\StandardizedPrimaryKey;
use Enum\ModalityType;
use Enum\ReliefPackageState;
use Entity\User;
use Entity\SmartcardDeposit;

/**
 * @ORM\Entity(repositoryClass="Repository\Assistance\ReliefPackageRepository")
 * @ORM\Table(name="assistance_relief_package")
 * @ORM\HasLifecycleCallbacks
 */
class ReliefPackage
{
    use StandardizedPrimaryKey;
    use CreatedAt;
    use LastModifiedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="enum_relief_package_state", nullable=false)
     */
    private $state;

    /**
     * @var AssistanceBeneficiary
     *
     * @ORM\ManyToOne(targetEntity="Entity\AssistanceBeneficiary", inversedBy="reliefPackages")
     */
    private $assistanceBeneficiary;

    /**
     * @var string
     *
     * @ORM\Column(name="modality_type", type="enum_modality_type", nullable=false)
     */
    private $modalityType;

    /**
     * @var string
     *
     * @ORM\Column(name="amount_to_distribute", type="decimal", precision=10, scale=2)
     */
    private $amountToDistribute;

    /**
     * @var string
     *
     * Not in use right now. Prepared for partial assists.
     *
     * @ORM\Column(name="amount_distributed", type="decimal", precision=10, scale=2)
     */
    private $amountDistributed;

    /**
     * @var string
     * 
     * controlled by database triggers on smartcard_payment_record table
     *
     * @ORM\Column(name="amount_spent", type="decimal", precision=10, scale=2)
     */
    private $amountSpent;

    /**
     * @var string
     *
     * @ORM\Column(name="unit", type="string", nullable=false)
     */
    private $unit;

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="string", length=255, nullable=true)
     */
    private $notes;

    /**
     * @var Collection|SmartcardDeposit[]
     *
     * There should be only one deposit at this moment. One-to-many prepared for partial distribution
     *
     * @ORM\OneToMany(targetEntity="Entity\SmartcardDeposit", mappedBy="reliefPackage")
     */
    private $smartcardDeposits;

    /**
     * @var DateTimeInterface|null
     *
     * @ORM\Column(name="distributedAt", type="datetime", nullable=true)
     */
    private $distributedAt;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="Entity\User")
     * @ORM\JoinColumn(nullable=true)
     */
    private $distributedBy;

    /**
     * @param AssistanceBeneficiary $assistanceBeneficiary
     * @param string $modalityType
     * @param float|string|int $amountToDistribute
     * @param string $unit
     * @param string $state
     * @param float|string|int $amountDistributed
     */
    public function __construct(
        AssistanceBeneficiary $assistanceBeneficiary,
        string $modalityType,
        $amountToDistribute,
        string $unit,
        string $state = ReliefPackageState::TO_DISTRIBUTE,
        $amountDistributed = 0.0
    ) {
        if (!in_array($modalityType, ModalityType::values())) {
            throw new InvalidArgumentException("Argument '$modalityType' isn't valid ModalityType");
        }

        if (!is_numeric($amountToDistribute)) {
            throw new InvalidArgumentException("amountToDistribute has to bee numeric. Provided value: '$amountToDistribute'");
        }

        if (!is_numeric($amountDistributed)) {
            throw new InvalidArgumentException("amountDistributed has to bee numeric. Provided value: '$amountDistributed'");
        }

        $this->assistanceBeneficiary = $assistanceBeneficiary;
        $this->modalityType = $modalityType;
        $this->amountToDistribute = (string) $amountToDistribute;
        $this->unit = $unit;
        $this->state = $state;
        $this->amountDistributed = (string) $amountDistributed;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @return AssistanceBeneficiary
     */
    public function getAssistanceBeneficiary(): AssistanceBeneficiary
    {
        return $this->assistanceBeneficiary;
    }

    /**
     * @param AssistanceBeneficiary $assistanceBeneficiary
     */
    public function setAssistanceBeneficiary(AssistanceBeneficiary $assistanceBeneficiary): void
    {
        $this->assistanceBeneficiary = $assistanceBeneficiary;
    }

    /**
     * @return string
     */
    public function getModalityType(): string
    {
        return $this->modalityType;
    }

    /**
     * @param string $modalityType
     */
    public function setModalityType(string $modalityType): void
    {
        if (!in_array($modalityType, ModalityType::values())) {
            throw new InvalidArgumentException("Argument '$modalityType' isn't valid ModalityType");
        }
        $this->modalityType = $modalityType;
    }

    /**
     * @return string
     */
    public function getAmountToDistribute(): string
    {
        return $this->amountToDistribute;
    }

    /**
     * @param float|string|int $amountToDistribute
     */
    public function setAmountToDistribute($amountToDistribute): void
    {
        if (!is_numeric($amountToDistribute)) {
            throw new InvalidArgumentException("amountToDistribute has to bee numeric. Provided value: '$amountToDistribute'");
        }

        $this->amountToDistribute = $amountToDistribute;
    }

    /**
     * @return string
     */
    public function getUnit(): string
    {
        return $this->unit;
    }

    /**
     * @param string $unit
     */
    public function setUnit(string $unit): void
    {
        $this->unit = $unit;
    }

    /**
     * @return string
     */
    public function getAmountDistributed(): string
    {
        return $this->amountDistributed;
    }

    /**
     * @param string $amountDistributed
     */
    public function setAmountDistributed(string $amountDistributed): void
    {
        $this->amountDistributed = $amountDistributed;
    }

    /**
     * @param int|float|string $amountDistributed
     *
     * @return void
     */
    public function addDistributedAmount($amountDistributed): void
    {
        $this->setAmountDistributed((string) ((float) $this->amountDistributed + (float) $amountDistributed));
    }

    public function distributeRest(): void
    {
        $this->addDistributedAmount($this->getCurrentUndistributedAmount());
    }

    /**
     * @return float
     */
    public function getCurrentUndistributedAmount(): float
    {
        return (float) $this->getAmountToDistribute() - $this->getAmountDistributed();
    }

    /**
     * @return bool
     */
    public function isFullyDistributed(): bool
    {
        return round($this->getCurrentUndistributedAmount(), 2) == 0;
    }

    /**
     * @return string
     */
    public function getAmountSpent(): ?string
    {
        return $this->amountSpent;
    }

    /**
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @param string|null $notes
     */
    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * @return Collection|SmartcardDeposit[]
     */
    public function getSmartcardDeposits()
    {
        return $this->smartcardDeposits;
    }

    /**
     * @param string $state
     */
    public function setState(string $state): void
    {
        $this->state = $state;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getDistributedAt(): ?DateTimeInterface
    {
        return $this->distributedAt;
    }

    /**
     * @param DateTimeInterface|null $distributedAt
     */
    public function setDistributedAt(?DateTimeInterface $distributedAt): void
    {
        $this->distributedAt = $distributedAt;
    }

    /**
     * @return User|null
     */
    public function getDistributedBy(): ?User
    {
        return $this->distributedBy;
    }

    /**
     * @param User|null $distributedBy
     */
    public function setDistributedBy(?User $distributedBy): void
    {
        $this->distributedBy = $distributedBy;
    }

    /**
     * @return bool
     */
    public function isOnStartupState(): bool
    {
        return in_array($this->state, ReliefPackageState::startupValues());
    }

    /**
     * @param string $modalityName
     * @param string $unit
     *
     * @return bool
     */
    public function isSameModalityAndUnit(string $modalityName, string $unit): bool
    {
        return $this->getModalityType() === $modalityName && $this->getUnit() === $unit;
    }
}
