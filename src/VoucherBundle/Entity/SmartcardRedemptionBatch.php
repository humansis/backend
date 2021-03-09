<?php

declare(strict_types=1);

namespace VoucherBundle\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use UserBundle\Entity\User;

/**
 * Smartcard purchase batch for redemption feature.
 *
 * @ORM\Table(name="smartcard_redemption_batch")
 * @ORM\Entity(repositoryClass="\VoucherBundle\Repository\SmartcardRedemptionBatchRepository")
 */
class SmartcardRedemptionBatch implements JsonSerializable
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Vendor
     *
     * @ORM\ManyToOne(targetEntity="\VoucherBundle\Entity\Vendor")
     * @ORM\JoinColumn(nullable=false)
     */
    private $vendor;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="redeemed_at", type="datetime", nullable=false)
     */
    private $redeemedAt;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     * @ORM\JoinColumn(name="redeemed_by", nullable=false)
     */
    private $redeemedBy;

    /**
     * @var mixed
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", nullable=true)
     */
    private $currency;

    /**
     * @var Collection|SmartcardPurchase[]
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\SmartcardPurchase", mappedBy="redemptionBatch", cascade={"persist"}, orphanRemoval=false)
     */
    private $purchases;

    /**
     * SmartcardPurchaseBatch constructor.
     *
     * @param Vendor   $vendor
     * @param DateTime $redeemedAt
     * @param User     $redeemedBy
     * @param mixed    $value
     * @param string   $currency
     * @param iterable $purchases
     */
    public function __construct(Vendor $vendor, DateTime $redeemedAt, User $redeemedBy, $value, string $currency,
                                iterable $purchases)
    {
        $this->vendor = $vendor;
        $this->redeemedAt = $redeemedAt;
        $this->redeemedBy = $redeemedBy;
        $this->value = $value;
        $this->currency = $currency;
        $this->purchases = new ArrayCollection($purchases);
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
     * @return Vendor
     */
    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getRedeemedAt(): ?DateTimeInterface
    {
        return $this->redeemedAt;
    }

    /**
     * @param DateTimeInterface|null $redeemedAt
     */
    public function setRedeemedAt(?DateTimeInterface $redeemedAt): void
    {
        $this->redeemedAt = $redeemedAt;
    }

    /**
     * @return User
     */
    public function getRedeemedBy(): User
    {
        return $this->redeemedBy;
    }

    /**
     * @param User $redeemedBy
     */
    public function setRedeemedBy(User $redeemedBy): void
    {
        $this->redeemedBy = $redeemedBy;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return Collection|SmartcardPurchase[]
     */
    public function getPurchases(): Collection
    {
        return $this->purchases;
    }

    /**
     * @param Collection|SmartcardPurchase[] $purchases
     */
    public function setPurchases($purchases): void
    {
        $this->purchases = $purchases;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'datetime' => $this->redeemedAt->format('U'),
            'date' => $this->redeemedAt->format('d-m-Y H:i'),
            'count' => $this->purchases->count(),
            'value' => (float) $this->value,
            'currency' => $this->currency,
        ];
    }
}
