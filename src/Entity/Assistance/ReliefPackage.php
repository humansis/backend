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
     * @ORM\Column(name="state", type="enum_relief_package_state", nullable=false)
     */
    private string $state;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\AssistanceBeneficiary", inversedBy="reliefPackages")
     */
    private AssistanceBeneficiary $assistanceBeneficiary;

    /**
     * @ORM\Column(name="modality_type", type="enum_modality_type", nullable=false)
     */
    private string $modalityType;

    /**
     * @ORM\Column(name="amount_to_distribute", type="decimal", precision=10, scale=2)
     */
    private string|float $amountToDistribute;

    /**
     *
     * Not in use right now. Prepared for partial assists.
     * @ORM\Column(name="amount_distributed", type="decimal", precision=10, scale=2)
     */
    private string $amountDistributed;

    /**
     *
     * controlled by database triggers on smartcard_payment_record table
     * @ORM\Column(name="amount_spent", type="decimal", precision=10, scale=2, nullable=true)
     */
    private ?string $amountSpent = null;

    /**
     * @ORM\Column(name="unit", type="string", nullable=false)
     */
    private string $unit;

    /**
     * @ORM\Column(name="notes", type="string", length=255, nullable=true)
     */
    private ?string $notes = null;

    /**
     * @var Collection|SmartcardDeposit[]
     *
     * There should be only one deposit at this moment. One-to-many prepared for partial distribution
     *
     * @ORM\OneToMany(targetEntity="Entity\SmartcardDeposit", mappedBy="reliefPackage")
     */
    private \Doctrine\Common\Collections\Collection|array $smartcardDeposits;

    /**
     * @ORM\Column(name="distributedAt", type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $distributedAt = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Entity\User")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?\Entity\User $distributedBy = null;

    /**
     * @param float|string|int $amountToDistribute
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
            throw new InvalidArgumentException(
                "amountToDistribute has to bee numeric. Provided value: '$amountToDistribute'"
            );
        }

        if (!is_numeric($amountDistributed)) {
            throw new InvalidArgumentException(
                "amountDistributed has to bee numeric. Provided value: '$amountDistributed'"
            );
        }
        $this->assistanceBeneficiary = $assistanceBeneficiary;
        $this->unit = $unit;
        $this->state = $state;
        $this->modalityType = $modalityType;
        $this->amountToDistribute = (string) $amountToDistribute;
        $this->amountDistributed = (string) $amountDistributed;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getAssistanceBeneficiary(): AssistanceBeneficiary
    {
        return $this->assistanceBeneficiary;
    }

    public function setAssistanceBeneficiary(AssistanceBeneficiary $assistanceBeneficiary): void
    {
        $this->assistanceBeneficiary = $assistanceBeneficiary;
    }

    public function getModalityType(): string
    {
        return $this->modalityType;
    }

    public function setModalityType(string $modalityType): void
    {
        if (!in_array($modalityType, ModalityType::values())) {
            throw new InvalidArgumentException("Argument '$modalityType' isn't valid ModalityType");
        }
        $this->modalityType = $modalityType;
    }

    public function getAmountToDistribute(): string
    {
        return $this->amountToDistribute;
    }

    public function setAmountToDistribute(float|string|int $amountToDistribute): void
    {
        if (!is_numeric($amountToDistribute)) {
            throw new InvalidArgumentException(
                "amountToDistribute has to bee numeric. Provided value: '$amountToDistribute'"
            );
        }

        $this->amountToDistribute = (string) $amountToDistribute;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): void
    {
        $this->unit = $unit;
    }

    public function getAmountDistributed(): string
    {
        return $this->amountDistributed;
    }

    public function setAmountDistributed(string $amountDistributed): void
    {
        $this->amountDistributed = $amountDistributed;
    }

    public function addDistributedAmount(int|float|string $amountDistributed): void
    {
        $this->setAmountDistributed((string) ((float) $this->amountDistributed + (float) $amountDistributed));
    }

    public function distributeRest(): void
    {
        $this->addDistributedAmount($this->getCurrentUndistributedAmount());
    }

    public function getCurrentUndistributedAmount(): float
    {
        return (float) $this->getAmountToDistribute() - $this->getAmountDistributed();
    }

    public function isFullyDistributed(): bool
    {
        return round($this->getCurrentUndistributedAmount(), 2) == 0;
    }

    public function getAmountSpent(): ?string
    {
        return $this->amountSpent;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * @return Collection|SmartcardDeposit[]
     */
    public function getSmartcardDeposits(): \Doctrine\Common\Collections\Collection|array
    {
        return $this->smartcardDeposits;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getDistributedAt(): ?DateTimeInterface
    {
        return $this->distributedAt;
    }

    public function setDistributedAt(?DateTimeInterface $distributedAt): void
    {
        $this->distributedAt = $distributedAt;
    }

    public function getDistributedBy(): ?User
    {
        return $this->distributedBy;
    }

    public function setDistributedBy(?User $distributedBy): void
    {
        $this->distributedBy = $distributedBy;
    }

    public function isOnStartupState(): bool
    {
        return in_array($this->state, ReliefPackageState::startupValues());
    }

    public function isSameModalityAndUnit(string $modalityName, string $unit): bool
    {
        return $this->getModalityType() === $modalityName && $this->getUnit() === $unit;
    }
}
