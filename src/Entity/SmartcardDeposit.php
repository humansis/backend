<?php

namespace Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CreatedAt;
use Entity\Assistance\ReliefPackage;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Smartcard deposit.
 */
#[ORM\Table(name: 'smartcard_deposit')]
#[ORM\UniqueConstraint(name: 'unique_deposit_hash', columns: ['hash'])]
#[ORM\Entity(repositoryClass: 'Repository\SmartcardDepositRepository')]
#[ORM\HasLifecycleCallbacks]
class SmartcardDeposit
{
    use CreatedAt;

    #[SymfonyGroups(['FullSmartcard'])]
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id;

    #[SymfonyGroups(['FullSmartcard'])]
    #[ORM\ManyToOne(targetEntity: 'Entity\SmartcardBeneficiary', inversedBy: 'deposites')]
    #[ORM\JoinColumn(nullable: false)]
    private SmartcardBeneficiary $smartcardBeneficiary;

    #[SymfonyGroups(['FullSmartcard'])]
    #[ORM\ManyToOne(targetEntity: 'Entity\User')]
    #[ORM\JoinColumn(nullable: false)]
    private User $distributedBy;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'distributed_at', type: 'datetime', nullable: true)]
    private DateTime $distributedAt;

    #[ORM\ManyToOne(targetEntity: 'Entity\Assistance\ReliefPackage', inversedBy: 'smartcardDeposits')]
    #[ORM\JoinColumn(name: 'relief_package_id')]
    private ReliefPackage $reliefPackage;

    /**
     * @var float
     */
    #[SymfonyGroups(['FullSmartcard'])]
    #[ORM\Column(name: 'value', type: 'decimal', precision: 10, scale: 2, nullable: false)]
    private float $value;

    /**
     * @var float
     */
    #[SymfonyGroups(['FullSmartcard'])]
    #[ORM\Column(name: 'balance', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private float $balance;

    #[ORM\Column(name: 'suspicious', type: 'boolean', options: ['default' => false])]
    private bool $suspicious;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'message', type: 'simple_array', nullable: true, options: ['default' => null])]
    private ?array $message;

    #[ORM\Column(name: 'hash', type: 'string', nullable: false)]
    private string $hash;

    public function __construct(
        SmartcardBeneficiary $smartcardBeneficiary,
        User $distributedBy,
        ReliefPackage $reliefPackage,
        $value,
        $balance,
        DateTime $distributedAt,
        bool $suspicious = false,
        ?array $message = null
    ) {
        $this->smartcardBeneficiary = $smartcardBeneficiary;
        $this->distributedBy = $distributedBy;
        $this->reliefPackage = $reliefPackage;
        $this->value = $value;
        $this->balance = $balance;
        $this->distributedAt = $distributedAt;
        $this->suspicious = $suspicious;
        $this->message = $message;

        $this->generateHash();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSmartcard(): SmartcardBeneficiary
    {
        return $this->smartcardBeneficiary;
    }

    public function getDistributedBy(): User
    {
        return $this->distributedBy;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function getReliefPackage(): ?ReliefPackage
    {
        return $this->reliefPackage;
    }

    public function setReliefPackage(?ReliefPackage $reliefPackage): void
    {
        $this->reliefPackage = $reliefPackage;
    }

    public function getDistributedAt(): DateTime
    {
        return $this->distributedAt;
    }

    public function setDistributedAt(DateTime $distributedAt): void
    {
        $this->distributedAt = $distributedAt;
    }

    public function isSuspicious(): bool
    {
        return $this->suspicious;
    }

    public function setSuspicious(bool $suspicious): void
    {
        $this->suspicious = $suspicious;
    }

    public function getMessage(): ?array
    {
        return $this->message;
    }

    public function setMessage(?array $message): void
    {
        $this->message = $message;
    }

    public function addMessage(string $message): void
    {
        $this->message[] = $message;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    private function generateHash(): void
    {
        $this->hash = md5(
            $this->smartcardBeneficiary->getSerialNumber() .
            '-' .
            $this->value .
            '-' .
            $this->getReliefPackage()->getUnit() .
            '-' .
            $this->getReliefPackage()->getId()
        );
    }
}
