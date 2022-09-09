<?php

namespace VoucherBundle\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\CreatedAt;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

use UserBundle\Entity\User;

/**
 * Smartcard deposit.
 *
 * @ORM\Table(name="smartcard_deposit")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\SmartcardDepositRepository")
 * @ORM\HasLifecycleCallbacks
 */
class SmartcardDeposit
{
    use CreatedAt;
    use StandardizedPrimaryKey;

    /**
     * @var Smartcard
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\Smartcard", inversedBy="deposites")
     * @ORM\JoinColumn(nullable=false)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $smartcard;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $distributedBy;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="distributed_at", type="datetime", nullable=true)
     */
    private $distributedAt;

    /**
     * @var ReliefPackage|null
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Assistance\ReliefPackage", inversedBy="smartcardDeposits")
     * @ORM\JoinColumn(name="relief_package_id")
     */
    private $reliefPackage;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2, nullable=false)
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $value;

    /**
     * @var float
     *
     * @ORM\Column(name="balance", type="decimal", precision=10, scale=2, nullable=true)
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $balance;

    /**
     * @var bool
     *
     * @ORM\Column(name="suspicious", type="boolean", options={"default": false})
     */
    private $suspicious;

    /**
     * @var array|null
     *
     * @ORM\Column(name="message", type="simple_array", nullable=true, options={"default": null})
     */
    private $message;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hash", type="string", nullable=true)
     */
    private $hash;

    public static function create(
        Smartcard         $smartcard,
        User              $distributedBy,
        ReliefPackage     $reliefPackage,
                          $value,
                          $balance,
        DateTimeInterface $distributedAt,
        string            $hash,
        bool              $suspicious = false,
        ?array            $message = null
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

    /**
     * @return Smartcard
     */
    public function getSmartcard(): Smartcard
    {
        return $this->smartcard;
    }

    /**
     * @return User
     */
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

    /**
     * @return ReliefPackage|null
     */
    public function getReliefPackage(): ?ReliefPackage
    {
        return $this->reliefPackage;
    }

    /**
     * @param ReliefPackage|null $reliefPackage
     */
    public function setReliefPackage(?ReliefPackage $reliefPackage): void
    {
        $this->reliefPackage = $reliefPackage;
    }

    /**
     * @return DateTime
     */
    public function getDistributedAt(): DateTime
    {
        return $this->distributedAt;
    }

    /**
     * @param DateTime $distributedAt
     */
    public function setDistributedAt(DateTime $distributedAt): void
    {
        $this->distributedAt = $distributedAt;
    }

    /**
     * @return bool
     */
    public function isSuspicious(): bool
    {
        return $this->suspicious;
    }

    /**
     * @param bool $suspicious
     */
    public function setSuspicious(bool $suspicious): void
    {
        $this->suspicious = $suspicious;
    }

    /**
     * @return array|null
     */
    public function getMessage(): ?array
    {
        return $this->message;
    }

    /**
     * @param array|null $message
     */
    public function setMessage(?array $message): void
    {
        $this->message = $message;
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function addMessage(string $message): void
    {
        $this->message[] = $message;
    }

    /**
     * @return string|null
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * @param string|null $hash
     */
    public function setHash(?string $hash): void
    {
        $this->hash = $hash;
    }

}
