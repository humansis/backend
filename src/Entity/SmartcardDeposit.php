<?php

namespace Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CreatedAt;
use Entity\Assistance\ReliefPackage;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use Entity\User;

/**
 * Smartcard deposit.
 *
 * @ORM\Table(name="smartcard_deposit")
 * @ORM\Entity(repositoryClass="Repository\SmartcardDepositRepository")
 * @ORM\HasLifecycleCallbacks
 */
class SmartcardDeposit
{
    use CreatedAt;

    /**
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     */
    #[SymfonyGroups(['FullSmartcard'])]
    private ?int $id;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Entity\Smartcard", inversedBy="deposites")
     * @ORM\JoinColumn(nullable=false)
     *
     */
    #[SymfonyGroups(['FullSmartcard'])]
    private ?\Entity\Smartcard $smartcard = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Entity\User")
     * @ORM\JoinColumn(nullable=false)
     *
     */
    #[SymfonyGroups(['FullSmartcard'])]
    private ?\Entity\User $distributedBy = null;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="distributed_at", type="datetime", nullable=true)
     */
    private $distributedAt;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Entity\Assistance\ReliefPackage", inversedBy="smartcardDeposits")
     * @ORM\JoinColumn(name="relief_package_id")
     */
    private ?\Entity\Assistance\ReliefPackage $reliefPackage = null;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2, nullable=false)
     */
    #[SymfonyGroups(['FullSmartcard'])]
    private $value;

    /**
     * @var float
     *
     * @ORM\Column(name="balance", type="decimal", precision=10, scale=2, nullable=true)
     */
    #[SymfonyGroups(['FullSmartcard'])]
    private $balance;

    /**
     * @ORM\Column(name="suspicious", type="boolean", options={"default": false})
     */
    private ?bool $suspicious = null;

    /**
     * @var array|null
     *
     * @ORM\Column(name="message", type="simple_array", nullable=true, options={"default": null})
     */
    private $message;

    /**
     * @ORM\Column(name="hash", type="string", nullable=true)
     */
    private ?string $hash = null;

    public static function create(
        Smartcard $smartcard,
        User $distributedBy,
        ReliefPackage $reliefPackage,
        $value,
        $balance,
        DateTimeInterface $distributedAt,
        string $hash,
        bool $suspicious = false,
        ?array $message = null
    ): SmartcardDeposit {
        $entity = new self();
        $entity->distributedBy = $distributedBy;
        $entity->distributedAt = $distributedAt;
        $entity->reliefPackage = $reliefPackage;
        $entity->value = $value;
        $entity->balance = $balance;
        $entity->smartcard = $smartcard;
        $entity->suspicious = $suspicious;
        $entity->hash = $hash;
        $entity->message = $message;

        $smartcard->addDeposit($entity);

        return $entity;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getSmartcard(): Smartcard
    {
        return $this->smartcard;
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

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): void
    {
        $this->hash = $hash;
    }
}
