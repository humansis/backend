<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use NewApiBundle\Entity\Helper\CreatedAt;
use NewApiBundle\Enum\ModalityType;
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
     * @ORM\Column(name="unit", type="string", nullable=false)
     */
    private $unit;

    /**
     * @var Collection|SmartcardDeposit[]
     *
     * There should be only one deposit at this moment. One-to-many prepared for partial distribution
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\SmartcardDeposit", mappedBy="reliefPackage")
     */
    private $smartcardDeposits;

    /**
     * @param AssistanceBeneficiary $assistanceBeneficiary
     * @param string                $modalityType
     * @param float|string|int      $amountToDistribute
     * @param string                $unit
     * @param string                $state
     * @param float|string|int      $amountDistributed
     */
    public function __construct(
        AssistanceBeneficiary $assistanceBeneficiary,
        string $modalityType,
        $amountToDistribute,
        string $unit,
        string $state = ReliefPackageState::TO_DISTRIBUTE,
        $amountDistributed = 0.0
    )
    {
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
     * @return float
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
