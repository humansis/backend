<?php

declare(strict_types=1);

namespace Entity;

use Entity\Beneficiary;
use DateTime;
use DateTimeInterface;
use Entity\Assistance;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Smartcard purchase.
 *
 * @ORM\Table(name="smartcard_purchase")
 * @ORM\Entity(repositoryClass="Repository\SmartcardPurchaseRepository")
 */
class SmartcardPurchase
{
    use StandardizedPrimaryKey;

    /**
     * @var Smartcard
     *
     * @ORM\ManyToOne(targetEntity="Entity\Smartcard", inversedBy="purchases")
     * @ORM\JoinColumn(nullable=false)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $smartcard;

    /**
     * @var Vendor
     *
     * @ORM\ManyToOne(targetEntity="\Entity\Vendor")
     * @ORM\JoinColumn(nullable=false)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $vendor;

    /**
     * @var Collection|SmartcardPurchaseRecord[]
     *
     * @ORM\OneToMany(targetEntity="Entity\SmartcardPurchaseRecord", mappedBy="smartcardPurchase", cascade={"persist"}, orphanRemoval=true)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $records;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="used_at", type="datetime", nullable=true)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $createdAt;

    /**
     * @var Invoice
     *
     * @ORM\ManyToOne(targetEntity="Invoice", inversedBy="purchases", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $redemptionBatch;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hash", type="text")
     */
    private $hash;

    /**
     * @var Assistance|null
     *
     * @ORM\ManyToOne(targetEntity="Entity\Assistance", inversedBy="smartcardPurchases", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $assistance;

    protected function __construct()
    {
        $this->records = new ArrayCollection();
    }

    public static function create(Smartcard $smartcard, Vendor $vendor, DateTimeInterface $createdAt, ?Assistance $assistance = null): SmartcardPurchase
    {
        $entity = new self();
        $entity->vendor = $vendor;
        $entity->createdAt = $createdAt;
        $entity->smartcard = $smartcard;
        $smartcard->addPurchase($entity);
        $entity->assistance = $assistance;

        return $entity;
    }

    /**
     * @return Smartcard
     */
    public function getSmartcard(): Smartcard
    {
        return $this->smartcard;
    }

    /**
     * @return Vendor
     */
    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    /**
     * @return Collection|SmartcardPurchaseRecord[]
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * @param Product $product
     * @param float|null $quantity
     * @param float|null $value
     * @param string|null $currency
     */
    public function addRecord(Product $product, ?float $quantity, ?float $value, ?string $currency): void
    {
        $this->records->add(SmartcardPurchaseRecord::create($this, $product, $quantity, $value, $currency));
    }

    public function getRecordsValue(): float
    {
        $purchased = 0;
        foreach ($this->getRecords() as $record) {
            $purchased += $record->getValue();
        }

        return $purchased;
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @SymfonyGroups({"FullSmartcard"})
     * @return DateTimeInterface|null
     */
    public function getRedeemedAt(): ?DateTimeInterface
    {
        return $this->redemptionBatch ? $this->redemptionBatch->getInvoicedAt() : null;
    }

    /**
     * @return Invoice|null
     */
    public function getRedemptionBatch(): ?Invoice
    {
        return $this->redemptionBatch;
    }

    /**
     * @param Invoice $invoice
     */
    public function setRedemptionBatch(Invoice $invoice): void
    {
        $this->redemptionBatch = $invoice;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->getRecords()->first()->getCurrency();
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

    /**
     * @return Assistance|null
     */
    public function getAssistance(): ?Assistance
    {
        return $this->assistance;
    }

    /**
     * @param Assistance|null $assistance
     */
    public function setAssistance(?Assistance $assistance): void
    {
        $this->assistance = $assistance;
    }
}
