<?php

namespace VoucherBundle\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AssistanceBeneficiaryCommodity;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

use UserBundle\Entity\User;

/**
 * Smartcard deposit.
 *
 * @ORM\Table(name="smartcard_deposit")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\SmartcardDepositRepository")
 */
class SmartcardDeposit
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $id;

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
     * @var AssistanceBeneficiaryCommodity
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\AssistanceBeneficiaryCommodity", inversedBy="smartcardDeposits")
     */
    private $assistanceBeneficiaryCommodity;

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
     * @var DateTime
     *
     * @ORM\Column(name="used_at", type="datetime", nullable=true)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $createdAt;

    protected function __construct()
    {
    }

    public static function create(
        Smartcard $smartcard,
        User $distributedBy,
        AssistanceBeneficiaryCommodity $assistanceBeneficiaryCommodity,
        $value,
        $balance,
        DateTimeInterface $distributedAt
    ) {
        $entity = new self();
        $entity->distributedBy = $distributedBy;
        $entity->distributedAt = $distributedAt;
        $entity->assistanceBeneficiaryCommodity = $assistanceBeneficiaryCommodity;
        $entity->value = $value;
        $entity->balance = $balance;
        $entity->smartcard = $smartcard;

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
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @return AssistanceBeneficiaryCommodity
     */
    public function getAssistanceBeneficiaryCommodity(): AssistanceBeneficiaryCommodity
    {
        return $this->assistanceBeneficiaryCommodity;
    }

    /**
     * @param AssistanceBeneficiaryCommodity $assistanceBeneficiaryCommodity
     */
    public function setAssistanceBeneficiaryCommodity(AssistanceBeneficiaryCommodity $assistanceBeneficiaryCommodity): void
    {
        $this->assistanceBeneficiaryCommodity = $assistanceBeneficiaryCommodity;
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
}
