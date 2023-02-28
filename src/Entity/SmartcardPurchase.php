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
     *
     * @ORM\ManyToOne(targetEntity="Entity\Smartcard", inversedBy="purchases")
     * @ORM\JoinColumn(nullable=false)
     *
     */
    #[SymfonyGroups(['FullSmartcard'])]
    private ?\Entity\Smartcard $smartcard = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="\Entity\Vendor")
     * @ORM\JoinColumn(nullable=false)
     *
     */
    #[SymfonyGroups(['FullSmartcard'])]
    private ?\Entity\Vendor $vendor = null;

    /**
     * @var Collection|SmartcardPurchaseRecord[]
     *
     * @ORM\OneToMany(targetEntity="Entity\SmartcardPurchaseRecord", mappedBy="smartcardPurchase", cascade={"persist"}, orphanRemoval=true)
     */
    #[SymfonyGroups(['FullSmartcard'])]
    private \Doctrine\Common\Collections\Collection|array $records;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="used_at", type="datetime", nullable=true)
     */
    #[SymfonyGroups(['FullSmartcard'])]
    private $createdAt;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Invoice", inversedBy="purchases", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private ?\Entity\Invoice $redemptionBatch = null;

    /**
     * @ORM\Column(name="hash", type="text")
     */
    private ?string $hash = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Entity\Assistance", inversedBy="smartcardPurchases", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private ?\Entity\Assistance $assistance = null;

    protected function __construct()
    {
        $this->records = new ArrayCollection();
    }

    public static function create(
        Smartcard $smartcard,
        Vendor $vendor,
        DateTimeInterface $createdAt,
        ?Assistance $assistance = null
    ): SmartcardPurchase {
        $entity = new self();
        $entity->vendor = $vendor;
        $entity->createdAt = $createdAt;
        $entity->smartcard = $smartcard;
        $smartcard->addPurchase($entity);
        $entity->assistance = $assistance;

        return $entity;
    }

    public function getSmartcard(): Smartcard
    {
        return $this->smartcard;
    }

    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    /**
     * @return Collection|SmartcardPurchaseRecord[]
     */
    public function getRecords(): \Doctrine\Common\Collections\Collection|array
    {
        return $this->records;
    }

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

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    #[SymfonyGroups(['FullSmartcard'])]
    public function getRedeemedAt(): ?DateTimeInterface
    {
        return $this->redemptionBatch?->getInvoicedAt();
    }

    public function getRedemptionBatch(): ?Invoice
    {
        return $this->redemptionBatch;
    }

    public function setRedemptionBatch(Invoice $invoice): void
    {
        $this->redemptionBatch = $invoice;
    }

    public function getCurrency(): string
    {
        return $this->getRecords()->first()->getCurrency();
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): void
    {
        $this->hash = $hash;
    }

    public function getAssistance(): ?Assistance
    {
        return $this->assistance;
    }

    public function setAssistance(?Assistance $assistance): void
    {
        $this->assistance = $assistance;
    }
}
