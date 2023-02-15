<?php

declare(strict_types=1);

namespace Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * Smartcard purchase batch for redemption feature.
 */
#[ORM\Table(name: 'smartcard_redemption_batch')]
#[ORM\Entity(repositoryClass: '\Repository\SmartcardInvoiceRepository')]
class Invoice implements JsonSerializable
{
    use StandardizedPrimaryKey;

    #[ORM\ManyToOne(targetEntity: '\Entity\Vendor')]
    #[ORM\JoinColumn(nullable: false)]
    private Vendor $vendor;

    #[ORM\ManyToOne(targetEntity: '\Entity\Project')]
    #[ORM\JoinColumn(nullable: true)]
    private Project | null $project;

    #[ORM\Column(name: 'redeemed_at', type: 'datetime', nullable: false)]
    private DateTimeInterface $invoicedAt;

    #[ORM\ManyToOne(targetEntity: 'Entity\User')]
    #[ORM\JoinColumn(name: 'redeemed_by', nullable: false)]
    private User $invoicedBy;

    #[ORM\Column(name: 'value', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private mixed $value;

    #[ORM\Column(name: 'currency', type: 'string', nullable: true)]
    private string $currency;

    #[ORM\Column(name: 'contract_no', type: 'string', nullable: true)]
    private string | null $contractNo;

    #[ORM\Column(name: 'vendor_no', type: 'string', nullable: true)]
    private string | null $vendorNo;

    #[ORM\Column(name: 'project_invoice_address_local', type: 'text', nullable: true, options: ['default' => null])]
    private ?string $projectInvoiceAddressLocal;

    #[ORM\Column(name: 'project_invoice_address_english', type: 'text', nullable: true, options: ['default' => null])]
    private ?string $projectInvoiceAddressEnglish;

    /**
     * @var Collection|SmartcardPurchase[]
     */
    #[ORM\OneToMany(mappedBy: 'redemptionBatch', targetEntity: 'Entity\SmartcardPurchase', cascade: ['persist'], orphanRemoval: false)]
    private Collection | array $purchases;

    /**
     * SmartcardPurchaseBatch constructor.
     */
    public function __construct(
        Vendor $vendor,
        ?Project $project,
        DateTime $redeemedAt,
        User $redeemedBy,
        mixed $value,
        string $currency,
        ?string $contractNo,
        ?string $vendorNo,
        array $purchases,
    ) {
        $this->vendor = $vendor;
        $this->project = $project;
        $this->invoicedAt = $redeemedAt;
        $this->invoicedBy = $redeemedBy;
        $this->value = $value;
        $this->currency = $currency;
        $this->purchases = new ArrayCollection($purchases);
        $this->contractNo = $contractNo;
        $this->vendorNo = $vendorNo;

        $this->projectInvoiceAddressLocal = $project?->getProjectInvoiceAddressLocal();
        $this->projectInvoiceAddressEnglish = $project?->getProjectInvoiceAddressEnglish();
    }

    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function getInvoicedAt(): DateTimeInterface
    {
        return $this->invoicedAt;
    }

    public function setInvoicedAt(DateTimeInterface $invoicedAt): void
    {
        $this->invoicedAt = $invoicedAt;
    }

    public function getInvoicedBy(): User
    {
        return $this->invoicedBy;
    }

    public function setInvoicedBy(User $invoicedBy): void
    {
        $this->invoicedBy = $invoicedBy;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

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
    public function setPurchases(Collection | array $purchases): void
    {
        $this->purchases = $purchases;
    }

    public function getContractNo(): ?string
    {
        return $this->contractNo;
    }

    public function getVendorNo(): ?string
    {
        return $this->vendorNo;
    }

    public function getInvoiceNo(): ?string
    {
        return $this->getId() ? sprintf('%06d', $this->getId()) : null;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'datetime' => $this->invoicedAt->format('U'),
            'date' => $this->invoicedAt->format('d-m-Y H:i'),
            'count' => $this->purchases->count(),
            'value' => (float) $this->value,
            'currency' => $this->currency,
            'contract_no' => $this->contractNo,
            'vendor_no' => $this->vendorNo,
            'invoice_number' => $this->getInvoiceNo(),
            'project_id' => $this->getProject() ? $this->getProject()->getId() : null,
            'project_name' => $this->getProject() ? $this->getProject()->getName() : null,
        ];
    }

    public function getProjectInvoiceAddressLocal(): ?string
    {
        return $this->projectInvoiceAddressLocal;
    }

    public function setProjectInvoiceAddressLocal(?string $projectInvoiceAddressLocal): void
    {
        $this->projectInvoiceAddressLocal = $projectInvoiceAddressLocal;
    }

    public function getProjectInvoiceAddressEnglish(): ?string
    {
        return $this->projectInvoiceAddressEnglish;
    }

    public function setProjectInvoiceAddressEnglish(?string $projectInvoiceAddressEnglish): void
    {
        $this->projectInvoiceAddressEnglish = $projectInvoiceAddressEnglish;
    }
}
