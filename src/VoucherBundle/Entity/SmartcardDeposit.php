<?php

namespace VoucherBundle\Entity;

use DateTime;
use DateTimeInterface;
use DistributionBundle\Entity\DistributionBeneficiary;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

use UserBundle\Entity\User;

/**
 * Smartcard deposit.
 *
 * @ORM\Table(name="smartcard_deposit")
 * @ORM\Entity()
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
    private $depositor;

    /**
     * @var DistributionBeneficiary
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\DistributionBeneficiary", inversedBy="smartcardDeposits")
     * @ORM\JoinColumn(nullable=false)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $distributionBeneficiary;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2, nullable=false)
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $value;

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
        User $depositor,
        DistributionBeneficiary $distributionBeneficiary,
        $value,
        DateTimeInterface $createdAt
    ) {
        $entity = new self();
        $entity->depositor = $depositor;
        $entity->distributionBeneficiary = $distributionBeneficiary;
        $entity->value = $value;
        $entity->createdAt = $createdAt;
        $entity->smartcard = $smartcard;

        $smartcard->addDeposit($entity);

        if (null === $smartcard->getCurrency()) {
            $smartcard->setCurrency(self::findCurrency($distributionBeneficiary));
        }

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
    public function getDepositor(): User
    {
        return $this->depositor;
    }

    /**
     * @return DistributionBeneficiary
     */
    public function getDistributionBeneficiary(): DistributionBeneficiary
    {
        return $this->distributionBeneficiary;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    private static function findCurrency(DistributionBeneficiary $distributionBeneficiary): string
    {
        foreach ($distributionBeneficiary->getAssistance()->getCommodities() as $commodity) {
            /** @var \DistributionBundle\Entity\Commodity $commodity */
            if ('Smartcard' === $commodity->getModalityType()->getName()) {
                return $commodity->getUnit();
            }
        }

        throw new \LogicException('Unable to find currency for DistributionBeneficiary #'.$distributionBeneficiary->getId());
    }
}
