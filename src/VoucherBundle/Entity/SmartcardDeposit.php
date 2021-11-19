<?php

namespace VoucherBundle\Entity;

use DateTime;
use DateTimeInterface;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\CreatedAt;
use NewApiBundle\Entity\ReliefPackage;
use NewApiBundle\Enum\ModalityType;
use NewApiBundle\Enum\ReliefPackageState;
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
     * @var ReliefPackage|null
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\ReliefPackage", inversedBy="smartcardDeposits")
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

    protected function __construct()
    {
    }

    public static function create(
        Smartcard             $smartcard,
        User                  $distributedBy,
        ReliefPackage         $reliefPackage,
                              $value,
                              $balance,
        DateTimeInterface     $distributedAt
    ) {
        $entity = new self();
        $entity->distributedBy = $distributedBy;
        $entity->distributedAt = $distributedAt;
        $entity->reliefPackage = $reliefPackage;
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
}
