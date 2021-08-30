<?php

namespace VoucherBundle\Entity;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Smartcard instance used by one Beneficiary
 *
 * @ORM\Table(name="smartcard")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\SmartcardRepository")
 */
class Smartcard
{
    const STATE_UNASSIGNED = 'unassigned';
    const STATE_ACTIVE = 'active';
    const STATE_INACTIVE = 'inactive';
    const STATE_CANCELLED = 'cancelled';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SymfonyGroups({"SmartcardOverview", "ValidatedAssistance"})
     */
    private $id = 0;

    /**
     * @var string serial number / UID
     *
     * @ORM\Column(name="code", type="string", length=14, unique=true, nullable=false)
     * @SymfonyGroups({"SmartcardOverview", "FullSmartcard", "ValidatedAssistance"})
     */
    private $serialNumber;

    /**
     * @var Beneficiary
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Beneficiary", inversedBy="smartcards")
     * @SymfonyGroups({"SmartcardOverview", "FullSmartcard"})
     */
    private $beneficiary;

    /**
     * @var Collection|SmartcardDeposit[]
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\SmartcardDeposit", mappedBy="smartcard", cascade={"persist"}, orphanRemoval=true)
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $deposites;

    /**
     * @var Collection|SmartcardPurchase[]
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\SmartcardPurchase", mappedBy="smartcard", cascade={"persist"}, orphanRemoval=true)
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $purchases;

    /**
     * @var string one of self::STATE_*
     *
     * @ORM\Column(name="state", type="string", length=10, nullable=false)
     * @SymfonyGroups({"SmartcardOverview", "FullSmartcard"})
     */
    private $state;

    /**
     * @var string|null
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     * @SymfonyGroups({"SmartcardOverview", "FullSmartcard"})
     */
    private $currency;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @SymfonyGroups({"SmartcardOverview", "FullSmartcard"})
     */
    private $createdAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="suspicious", type="boolean", nullable=false)
     */
    private $suspicious = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="suspicious_reason", type="string", nullable=true)
     */
    private $suspiciousReason;

    public function __construct(string $serialNumber, \DateTimeInterface $createdAt)
    {
        if (!self::check($serialNumber)) {
            throw new \InvalidArgumentException('Smartcard serial number '.$serialNumber.'is not valid');
        }

        $this->serialNumber = strtoupper($serialNumber);
        $this->createdAt = $createdAt;
        $this->deposites = new ArrayCollection();
        $this->purchases = new ArrayCollection();

        $this->state = self::STATE_UNASSIGNED;
    }

    public static function states(): array
    {
        return [
            self::STATE_UNASSIGNED,
            self::STATE_ACTIVE,
            self::STATE_INACTIVE,
            self::STATE_CANCELLED,
        ];
    }

    public static function check(string $serialNumber): bool
    {
        return preg_match('~^[A-F0-9]+$~i', $serialNumber);
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
    public function getSerialNumber(): string
    {
        return $this->serialNumber;
    }

    /**
     * @param Beneficiary $beneficiary
     *
     * @return self
     */
    public function setBeneficiary(Beneficiary $beneficiary): self
    {
        $this->beneficiary = $beneficiary;

        return $this;
    }

    /**
     * @return Beneficiary|null
     */
    public function getBeneficiary()
    {
        return $this->beneficiary;
    }

    /**
     * @return Collection|SmartcardPurchase[]
     */
    public function getPurchases(): iterable
    {
        return $this->purchases;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Currency used in smartcard.
     * Currency is defined after deposit money into card.
     *
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return $this
     */
    public function setCurrency(string $currency): self
    {
        if (null !== $this->currency) {
            throw new \BadMethodCallException('Currency is already defined for smartcard #'.$this->getId());
        }

        $this->currency = $currency;

        return $this;
    }

    /**
     * @param string $state
     *
     * @return $this
     */
    public function setState(string $state): self
    {
        if (!in_array($state, self::states())) {
            throw new \InvalidArgumentException(sprintf('Argument 1 must be one of [%s]. %s given.',
                implode(', ', self::states()), $state
            ));
        }

        $this->state = $state;

        return $this;
    }

    public function isSuspicious(): bool
    {
        return $this->suspicious;
    }

    public function setSuspicious(bool $suspicious, ?string $reason = null): self
    {
        if (true === $suspicious && true === $this->suspicious) {
            $reason = trim($this->suspiciousReason.', '.$reason);
        }

        $this->suspicious = $suspicious;
        $this->suspiciousReason = $reason;

        return $this;
    }

    public function getSuspiciousReason(): ?string
    {
        return $this->suspiciousReason;
    }

    /**
     * @return float
     *
     * @SymfonyGroups({"SmartcardOverview", "FullSmartcard"})
     */
    public function getValue(): float
    {
        $sum = 0.0;
        foreach ($this->deposites as $deposit) {
            $sum += $deposit->getValue();
        }

        foreach ($this->purchases as $purchase) {
            foreach ($purchase->getRecords() as $record) {
                $sum -= $record->getValue();
            }
        }

        return $sum;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function addDeposit(SmartcardDeposit $deposit): self
    {
        if (!$this->deposites->contains($deposit)) {
            $this->deposites->add($deposit);
        }

        return $this;
    }

    /**
     * @return Collection|SmartcardDeposit[]
     */
    public function getDeposites()
    {
        return $this->deposites;
    }

    public function addPurchase(SmartcardPurchase $purchase): self
    {
        if (!$this->purchases->contains($purchase)) {
            $this->purchases->add($purchase);
        }

        return $this;
    }

    /**
     * @return bool return true, if smartcard is valid and allow to pay
     */
    public function isActive(): bool
    {
        return self::STATE_ACTIVE === $this->state;
    }
}
