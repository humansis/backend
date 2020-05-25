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
    const STATE_FROZEN = 'frozen';
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
     * @ORM\Column(name="code", type="string", length=7, unique=true, nullable=false)
     * @SymfonyGroups({"SmartcardOverview", "FullSmartcard"})
     * @Serializer\Groups({"SmartcardOverview", "FullSmartcard"})
     */
    private $serialNumber;

    /**
     * @var Beneficiary
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Beneficiary")
     * @ORM\JoinColumn(nullable=false)
     * @SymfonyGroups({"SmartcardOverview", "FullSmartcard"})
     * @Serializer\Groups({"SmartcardOverview", "FullSmartcard"})
     */
    private $beneficiary;

    /**
     * @var Collection|SmartcardRecord[]
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\SmartcardRecord", mappedBy="smartcard", cascade={"persist"}, orphanRemoval=true)
     * @SymfonyGroups({"FullSmartcard"})
     * @Serializer\Groups({"FullSmartcard"})
     */
    private $records;

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
        $this->records = new ArrayCollection();
        $this->state = self::STATE_UNASSIGNED;
    }

    public static function states(): array
    {
        return [
            self::STATE_UNASSIGNED,
            self::STATE_ACTIVE,
            self::STATE_INACTIVE,
            self::STATE_FROZEN,
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
     * @param Beneficiary $beneficiary
     *
     * @return $this
     */
    public function setBeneficiary(Beneficiary $beneficiary): self
    {
        $this->beneficiary = $beneficiary;

        return $this;
    }

    /**
     * @return Collection|SmartcardRecord[]
     */
    public function getRecords(): iterable
    {
        return $this->records;
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
        foreach ($this->records as $record) {
            $sum += $record->getValue();
        }

        return $sum;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function addDeposit(float $value, \DateTimeInterface $createdAt): self
    {
        $value = abs($value); // deposit must be always positive

        $this->records->add(new SmartcardRecord($this, null, null, $value, $createdAt));

        return $this;
    }

    public function addPurchase(float $value, Product $product, float $quantity, \DateTimeInterface $createdAt): self
    {
        $value = -1 * abs($value); // payment must be always negative

        $this->records->add(new SmartcardRecord($this, $product, $quantity, $value, $createdAt));

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
