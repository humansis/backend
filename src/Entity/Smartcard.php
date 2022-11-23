<?php

namespace Entity;

use BadMethodCallException;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use Enum\SmartcardStates;

/**
 * Smartcard instance used by one Beneficiary
 *
 * @ORM\Table(name="smartcard")
 * @ORM\Entity(repositoryClass="Repository\SmartcardRepository")
 */
class Smartcard
{
    final public const STATE_UNASSIGNED = 'unassigned';
    final public const STATE_ACTIVE = 'active';
    final public const STATE_INACTIVE = 'inactive';
    final public const STATE_CANCELLED = 'cancelled';

    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    #[SymfonyGroups(['SmartcardOverview', 'ValidatedAssistance'])]
    private ?int $id = 0;

    /**
     * @var string serial number / UID
     *
     * @ORM\Column(name="code", type="string", length=14, unique=true, nullable=false)
     */
    #[SymfonyGroups(['SmartcardOverview', 'FullSmartcard', 'ValidatedAssistance'])]
    private string $serialNumber;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Beneficiary", inversedBy="smartcards")
     */
    #[SymfonyGroups(['SmartcardOverview', 'FullSmartcard'])]
    private ?Beneficiary $beneficiary = null;

    /**
     * @var Collection|SmartcardDeposit[]
     *
     * @ORM\OneToMany(targetEntity="Entity\SmartcardDeposit", mappedBy="smartcard", cascade={"persist"}, orphanRemoval=true)
     */
    #[SymfonyGroups(['FullSmartcard'])]
    private Collection |array $deposites;

    /**
     * @var Collection|SmartcardPurchase[]
     *
     * @ORM\OneToMany(targetEntity="Entity\SmartcardPurchase", mappedBy="smartcard", cascade={"persist"}, orphanRemoval=true)
     */
    #[SymfonyGroups(['FullSmartcard'])]
    private Collection |array $purchases;

    /**
     * @see SmartcardStates::all()
     * @ORM\Column(name="state", type="string", length=10, nullable=false)
     */
    #[SymfonyGroups(['SmartcardOverview', 'FullSmartcard'])]
    private string $state;

    /**
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    #[SymfonyGroups(['SmartcardOverview', 'FullSmartcard'])]
    private ?string $currency = null;

    /**
     * @ORM\Column(name="disabled_at", type="datetime", nullable=true)
     */
    #[SymfonyGroups(['SmartcardOverview', 'FullSmartcard'])]
    private ?DateTimeInterface $disabledAt = null;

    /**
     * @ORM\Column(name="registered_at", type="datetime", nullable=true)
     */
    private ?DateTimeInterface $registeredAt = null;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    #[SymfonyGroups(['SmartcardOverview', 'FullSmartcard'])]
    private DateTimeInterface $createdAt;

    /**
     * @ORM\Column(name="changed_at", type="datetime", nullable=true)
     */
    private ?DateTimeInterface $changedAt = null;

    /**
     * @ORM\Column(name="suspicious", type="boolean", nullable=false)
     */
    private bool $suspicious = false;

    /**
     * @ORM\Column(name="suspicious_reason", type="string", nullable=true)
     */
    private ?string $suspiciousReason = null;

    public function __construct(
        string $serialNumber,
        DateTimeInterface $createdAt,
    ) {
        if (!self::check($serialNumber)) {
            throw new InvalidArgumentException('Smartcard serial number ' . $serialNumber . 'is not valid');
        }

        $this->createdAt = $createdAt;
        $this->serialNumber = strtoupper($serialNumber);
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

    public function getId(): int
    {
        return $this->id;
    }

    public function getSerialNumber(): string
    {
        return $this->serialNumber;
    }

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

    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Currency used in smartcard.
     * Currency is defined after deposit money into card.
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @return $this
     */
    public function setCurrency(string $currency): self
    {
        if (null !== $this->currency) {
            throw new BadMethodCallException('Currency is already defined for smartcard #' . $this->getId());
        }

        $this->currency = $currency;

        return $this;
    }

    /**
     * @return $this
     */
    public function setState(string $state): self
    {
        if (!in_array($state, self::states())) {
            throw new InvalidArgumentException(
                sprintf(
                    'Argument 1 must be one of [%s]. %s given.',
                    implode(', ', self::states()),
                    $state
                )
            );
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
            $reason = trim($this->suspiciousReason . ', ' . $reason);
        }

        $this->suspicious = $suspicious;
        $this->suspiciousReason = $reason;

        return $this;
    }

    public function getSuspiciousReason(): ?string
    {
        return $this->suspiciousReason;
    }

    #[SymfonyGroups(['SmartcardOverview', 'FullSmartcard'])]
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

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getDisabledAt(): ?DateTimeInterface
    {
        return $this->disabledAt;
    }

    public function setDisabledAt(DateTimeInterface $disabledAt): void
    {
        $this->disabledAt = $disabledAt;
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
    public function getDeposites(): Collection |array
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

    public function getRegisteredAt(): ?DateTimeInterface
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(DateTimeInterface $registeredAt): void
    {
        $this->registeredAt = $registeredAt;
    }

    public function getChangedAt(): ?DateTimeInterface
    {
        return $this->changedAt;
    }

    public function setChangedAt(DateTimeInterface $changedAt): void
    {
        $this->changedAt = $changedAt;
    }
}
