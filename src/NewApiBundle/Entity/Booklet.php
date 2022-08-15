<?php

namespace NewApiBundle\Entity;

use NewApiBundle\Entity\AssistanceBeneficiary;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use NewApiBundle\Utils\ExportableInterface;
use NewApiBundle\Entity\Project;

/**
 * Booklet
 *
 * @ORM\Table(name="booklet")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\BookletRepository")
 */
class Booklet implements ExportableInterface
{
    public const UNASSIGNED = 0;
    public const DISTRIBUTED = 1;
    public const USED = 2;
    public const DEACTIVATED = 3;

    use StandardizedPrimaryKey;

    /**
     * @var ReliefPackage|null
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Assistance\ReliefPackage")
     * @ORM\JoinColumn(name="relief_package_id")
     */
    private $reliefPackage;

    /**
     * @var Project|null
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Project")
     * @SymfonyGroups({"FullBooklet", "ValidatedAssistance"})
     */
    private $project;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     * @SymfonyGroups({"FullBooklet", "ValidatedAssistance"})
     */
    private $code;

    /**
     * @var int
     *
     * @ORM\Column(name="number_vouchers", type="integer")
     * @SymfonyGroups({"FullBooklet"})
     */
    private $numberVouchers;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=255)
     * @SymfonyGroups({"FullBooklet", "ValidatedAssistance"})
     */
    private $currency;

    /**
     * @var int|null
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     * @SymfonyGroups({"FullBooklet", "ValidatedAssistance"})
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullBooklet"})
     */
    public $password;

    /**
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\Voucher", mappedBy="booklet", cascade={"persist"}, orphanRemoval=true)
     * @SymfonyGroups({"FullBooklet", "ValidatedAssistance"})
     */
    private $vouchers;

    /**
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\AssistanceBeneficiary", inversedBy="booklets")
     * @ORM\JoinColumn(name="distribution_beneficiary_id")
     */
    private $distribution_beneficiary;

    /**
     * @var string|null
     *
     * @ORM\Column(name="country_iso3", type="string", length=45)
     * @SymfonyGroups({"FullBooklet"})
     */
    private $countryISO3;

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
     * @SymfonyGroups({"FullBooklet"})
     * @return AssistanceBeneficiary|null
     */
    public function getDistributionBeneficiary(): ?AssistanceBeneficiary
    {
        return $this->getAssistanceBeneficiary();
    }

    /**
     * @return ReliefPackage|null
     */
    public function getReliefPackage(): ?ReliefPackage
    {
        return $this->reliefPackage;
    }

    /**
     * @param ReliefPackage|null $reliefPackage
     *
     * @return Booklet
     */
    public function setReliefPackage(?ReliefPackage $reliefPackage): Booklet
    {
        $this->reliefPackage = $reliefPackage;

        return $this;
    }


    /**
     * @return Project|null
     */
    public function getProject(): ?Project
    {
        return $this->project;
    }

    /**
     * @param Project|null $project
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
     * @param string $code
     *
     * @return Booklet
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set numberVouchers.
     *
     * @param int $numberVouchers
     *
     * @return Booklet
     */
    public function setNumberVouchers($numberVouchers)
    {
        $this->numberVouchers = $numberVouchers;

        return $this;
    }

    /**
     * Get numberVouchers.
     *
     * @return int
     */
    public function getNumberVouchers()
    {
        return $this->numberVouchers;
    }

    /**
     * Set currency.
     *
     * @param string $currency
     *
     * @return Booklet
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set status.
     *
     * @param int|null $status
     *
     * @return Booklet
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set password.
     *
     * @param string|null $password
     *
     * @return Booklet
     */
    public function setPassword($password = null)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string|null
     */
    public function getPassword()
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
     * @return array
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
            'Used at' => $this->getUsedAt()
        ];

        $vouchers = $this->getVouchers();

        foreach ($vouchers as $index => $voucher) {
            $displayIndex = $index + 1;
            $finalArray['Voucher '.$displayIndex] = $voucher->getValue().$this->getCurrency();
        }

        return $finalArray;
    }

    public function getTotalValue()
    {
        $vouchers = $this->getVouchers();
        $value = 0;
        foreach ($vouchers as $voucher) {
            $value += $voucher->getValue();
        }
        return $value;
    }

    /**
     * @return \DateTimeInterface|null Datetime last purchased voucher
     */
    public function getUsedAt()
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

    /**
     * Set countryISO3.
     *
     * @param string $countryISO3
     *
     * @return Booklet
     */
    public function setCountryISO3($countryISO3)
    {
        $this->countryISO3 = $countryISO3;

        return $this;
    }

    /**
     * Get countryISO3.
     *
     * @return string
     */
    public function getCountryISO3()
    {
        return $this->countryISO3;
    }
}
