<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\CreatedAt;
use NewApiBundle\Enum\ReliefPackageState;
use VoucherBundle\Entity\SmartcardDeposit;

/**
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\ReliefPackageRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ReliefPackage
{
    use CreatedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="enum_relief_package_state", nullable=false)
     */
    private $state;

    /**
     * @var AssistanceBeneficiary
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\AssistanceBeneficiary", inversedBy="reliefPackages")
     */
    private $assistanceBeneficiary;

    /**
     * @var string
     *
     * @ORM\Column(name="modality_type", type="enum_modality_type", nullable=false)
     */
    private $modalityType;

    /**
     * @var float
     *
     * @ORM\Column(name="amount_to_distribute", type="decimal", precision=10, scale=2)
     */
    private $amountToDistribute;

    /**
     * @var float
     *
     * Not in use right now. Prepared for partial assists.
     *
     * @ORM\Column(name="amount_distributed", type="decimal", precision=10, scale=2)
     */
    private $amountDistributed;

    /**
     * @var string
     *
     * @ORM\Column(name="unit", type="string", nullable=false)
     */
    private $unit;

    /**
     * @var Collection|SmartcardDeposit[]
     *
     * There should be only one deposit at this moment. One-to-many prepared for partial distribution
     *
     * @ORM\OneToMany(targetEntity="ReliefPackage", mappedBy="reliefPackage")
     * @ORM\JoinColumn(name="relief_package_id")
     */
    private $smartcardDeposits;

    /**
     * @param AssistanceBeneficiary $assistanceBeneficiary
     * @param string                $modalityType
     * @param float                 $amountToDistribute
     * @param string                $unit
     * @param string                $state
     * @param float                 $amountDistributed
     */
    public function __construct(
        AssistanceBeneficiary $assistanceBeneficiary,
        string $modalityType,
        float $amountToDistribute,
        string $unit,
        string $state = ReliefPackageState::TO_DISTRIBUTE,
        float $amountDistributed = 0.0
    )
    {
        $this->assistanceBeneficiary = $assistanceBeneficiary;
        $this->modalityType = $modalityType; //TODO check enum values
        $this->amountToDistribute = $amountToDistribute;
        $this->unit = $unit;
        $this->state = $state;
        $this->amountDistributed = $amountDistributed;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
        $this->modalityType = $modalityType;
    }

    /**
     * @return float
     */
    public function getAmountToDistribute(): float
    {
        return $this->amountToDistribute;
    }

    /**
     * @param float $amountToDistribute
     */
    public function setAmountToDistribute(float $amountToDistribute): void
    {
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
     * @return float
     */
    public function getAmountDistributed(): float
    {
        return $this->amountDistributed;
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
}
