<?php

namespace VoucherBundle\Entity;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Smartcard.
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
     * @SymfonyGroups({"SmartcardOverview"})
     */
    private $id = 0;

    /**
     * @var string serial number / UID
     *
     * @ORM\Column(name="code", type="string", length=14, unique=true, nullable=false)
     * @SymfonyGroups({"SmartcardOverview", "FullSmartcard"})
     * @Serializer\Groups({"SmartcardOverview", "FullSmartcard"})
     */
    private $serialNumber;

    /**
     * @var Beneficiary
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Beneficiary", inversedBy="smartcards")
     * @ORM\JoinColumn(nullable=false)
     * @SymfonyGroups({"SmartcardOverview", "FullSmartcard"})
     * @Serializer\Groups({"SmartcardOverview", "FullSmartcard"})
     */
    private $beneficiary;

    /**
     * @var Collection|SmartcardDeposit[]
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\SmartcardDeposit", mappedBy="smartcard", cascade={"persist"}, orphanRemoval=true)
     * @SymfonyGroups({"FullSmartcard"})
     * @Serializer\Groups({"FullSmartcard"})
     */
    private $deposites;

    /**
     * @var Collection|SmartcardPurchase[]
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\SmartcardPurchase", mappedBy="smartcard", cascade={"persist"}, orphanRemoval=true)
     * @SymfonyGroups({"FullSmartcard"})
     * @Serializer\Groups({"FullSmartcard"})
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
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="created_at", type="datetimetz", nullable=false)
     * @SymfonyGroups({"SmartcardOverview", "FullSmartcard"})
     */
    private $createdAt;

    public function __construct(string $serialNumber, Beneficiary $beneficiary, \DateTimeInterface $createdAt)
    {
        $this->serialNumber = $serialNumber;
        $this->beneficiary = $beneficiary;
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
     * @return mixed
     */
    public function getBeneficiary()
    {
        return $this->beneficiary;
    }

    /**
     * @return Collection|SmartcardPurchaseRecord[]
     */
    public function getRecords(): iterable
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
