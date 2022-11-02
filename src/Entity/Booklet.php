<?php

namespace Entity;

use DateTimeInterface;
use Entity\AssistanceBeneficiary;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Assistance\ReliefPackage;
use Entity\Helper\CountryDependent;
use Entity\Helper\StandardizedPrimaryKey;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use Utils\ExportableInterface;
use Entity\Project;

/**
 * Booklet
 *
 * @ORM\Table(name="booklet")
 * @ORM\Entity(repositoryClass="Repository\BookletRepository")
 */
class Booklet implements ExportableInterface
{
    use StandardizedPrimaryKey;
    use CountryDependent;

    final public const UNASSIGNED = 0;
    final public const DISTRIBUTED = 1;
    final public const USED = 2;
    final public const DEACTIVATED = 3;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Entity\Assistance\ReliefPackage")
     * @ORM\JoinColumn(name="relief_package_id")
     */
    private ?\Entity\Assistance\ReliefPackage $reliefPackage = null;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Project")
     */
    #[SymfonyGroups(['FullBooklet', 'ValidatedAssistance'])]
    private ?\Entity\Project $project = null;

    /**
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     */
    #[SymfonyGroups(['FullBooklet', 'ValidatedAssistance'])]
    private ?string $code = null;

    /**
     * @ORM\Column(name="number_vouchers", type="integer")
     */
    #[SymfonyGroups(['FullBooklet'])]
    private ?int $numberVouchers = null;

    /**
     * @ORM\Column(name="currency", type="string", length=255)
     */
    #[SymfonyGroups(['FullBooklet', 'ValidatedAssistance'])]
    private ?string $currency = null;

    /**
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    #[SymfonyGroups(['FullBooklet', 'ValidatedAssistance'])]
    private ?int $status = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     */
    #[SymfonyGroups(['FullBooklet'])]
    public $password;

    /**
     * @ORM\OneToMany(targetEntity="Entity\Voucher", mappedBy="booklet", cascade={"persist"}, orphanRemoval=true)
     */
    #[SymfonyGroups(['FullBooklet', 'ValidatedAssistance'])]
    private $vouchers;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\AssistanceBeneficiary", inversedBy="booklets")
     * @ORM\JoinColumn(name="distribution_beneficiary_id")
     */
    private $distribution_beneficiary;

    public static function statuses(): array
    {
        return [
            self::UNASSIGNED => 'Unassigned',
            self::DISTRIBUTED => 'Distributed',
            self::USED => 'Used',
            self::DEACTIVATED => 'Deactivated',
        ];
    }

    public function __construct()
    {
        $this->vouchers = new ArrayCollection();
    }

    /**
     * @deprecated use getAssistanceBeneficiary instead if you can
     */
    #[SymfonyGroups(['FullBooklet'])]
    public function getDistributionBeneficiary(): ?AssistanceBeneficiary
    {
        return $this->getAssistanceBeneficiary();
    }

    public function getReliefPackage(): ?ReliefPackage
    {
        return $this->reliefPackage;
    }

    public function setReliefPackage(?ReliefPackage $reliefPackage): Booklet
    {
        $this->reliefPackage = $reliefPackage;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    /**
     * @return $this
     */
    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Set code.
     *
     *
     */
    public function setCode(string $code): Booklet
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Set numberVouchers.
     *
     *
     */
    public function setNumberVouchers(int $numberVouchers): Booklet
    {
        $this->numberVouchers = $numberVouchers;

        return $this;
    }

    /**
     * Get numberVouchers.
     */
    public function getNumberVouchers(): int
    {
        return $this->numberVouchers;
    }

    /**
     * Set currency.
     *
     *
     */
    public function setCurrency(string $currency): Booklet
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency.
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Set status.
     *
     *
     */
    public function setStatus(?int $status = null): Booklet
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * Set password.
     *
     *
     */
    public function setPassword(?string $password = null): Booklet
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @return Collection|Voucher[]
     */
    public function getVouchers(): Collection
    {
        return $this->vouchers;
    }

    public function addVoucher(Voucher $voucher): self
    {
        if (!$this->vouchers->contains($voucher)) {
            $this->vouchers[] = $voucher;
            $voucher->setBooklet($this);
        }

        return $this;
    }

    public function removeVoucher(Voucher $voucher): self
    {
        if ($this->vouchers->contains($voucher)) {
            $this->vouchers->removeElement($voucher);
            // set the owning side to null (unless already changed)
            if ($voucher->getBooklet() === $this) {
                $voucher->setBooklet(null);
            }
        }

        return $this;
    }

    public function getAssistanceBeneficiary(): ?AssistanceBeneficiary
    {
        return $this->distribution_beneficiary;
    }

    public function setAssistanceBeneficiary(AssistanceBeneficiary $assistanceBeneficiary): self
    {
        $this->distribution_beneficiary = $assistanceBeneficiary;

        return $this;
    }

    /**
     * Returns an array representation of this class in order to prepare the export
     */
    public function getMappedValueForExport(): array
    {
        if ($this->getStatus() === 0) {
            $status = 'Unassigned';
        } elseif ($this->getStatus() === 1) {
            $status = 'Distributed';
        } elseif ($this->getStatus() === 2) {
            $status = 'Used';
        } elseif ($this->getStatus() === 3) {
            $status = 'Deactivated';
        } else {
            $status = 'Unknown';
        }

        $password = empty($this->getPassword()) ? 'No' : 'Yes';
        $distribution = $this->getAssistanceBeneficiary() ?
            $this->getAssistanceBeneficiary()->getAssistance()->getName() :
            null;
        $beneficiary = $this->getAssistanceBeneficiary() ?
            $this->getAssistanceBeneficiary()->getBeneficiary()->getLocalGivenName() :
            null;

        $finalArray = [
            'Code' => $this->getCode(),
            'Quantity of vouchers' => $this->getNumberVouchers(),
            'Status' => $status,
            'Password' => $password,
            'Beneficiary' => $beneficiary,
            'Distribution' => $distribution,
            'Total value' => $this->getTotalValue(),
            'Currency' => $this->getCurrency(),
            'Used at' => $this->getUsedAt(),
        ];

        $vouchers = $this->getVouchers();

        foreach ($vouchers as $index => $voucher) {
            $displayIndex = $index + 1;
            $finalArray['Voucher ' . $displayIndex] = $voucher->getValue() . $this->getCurrency();
        }

        return $finalArray;
    }

    public function getTotalValue(): int
    {
        $vouchers = $this->getVouchers();
        $value = 0;
        foreach ($vouchers as $voucher) {
            $value += $voucher->getValue();
        }

        return $value;
    }

    /**
     * @return DateTimeInterface|null Datetime last purchased voucher
     */
    public function getUsedAt(): ?DateTimeInterface
    {
        $date = null;
        if (in_array($this->getStatus(), [self::USED, self::DEACTIVATED])) {
            foreach ($this->getVouchers() as $voucher) {
                $purchase = $voucher->getVoucherPurchase();
                if (null !== $purchase && (null === $date || 0 === $purchase->getCreatedAt()->diff($date)->invert)) {
                    $date = $purchase->getCreatedAt();
                }
            }
        }

        return $date;
    }
}
