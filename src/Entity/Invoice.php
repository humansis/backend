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
use Entity\Project;
use Entity\User;

/**
 * Smartcard purchase batch for redemption feature.
 *
 * @ORM\Table(name="smartcard_redemption_batch")
 * @ORM\Entity(repositoryClass="\Repository\SmartcardInvoiceRepository")
 */
class Invoice implements JsonSerializable
{
    use StandardizedPrimaryKey;

    /**
     * @ORM\Column(name="project_invoice_address_local", type="text", nullable=true, options={"default" : null})
     */
    private ?string $projectInvoiceAddressLocal;

    /**
     * @ORM\Column(name="project_invoice_address_english", type="text", nullable=true, options={"default" : null})
     */
    private ?string $projectInvoiceAddressEnglish;

    /**
     * @var Collection|SmartcardPurchase[]
     *
     * @ORM\OneToMany(targetEntity="Entity\SmartcardPurchase", mappedBy="redemptionBatch", cascade={"persist"}, orphanRemoval=false)
     */
    private \Doctrine\Common\Collections\Collection|array $purchases;

    /**
     * SmartcardPurchaseBatch constructor.
     */
    public function __construct(
        private Vendor $vendor,
        private ?\Entity\Project $project,
        private DateTime $invoicedAt,
        private User $invoicedBy,
        private mixed $value,
        private string $currency,
        private ?string $contractNo,
        private ?string $vendorNo,
        array $purchases
    ) {
        $this->purchases = new ArrayCollection($purchases);

        $this->projectInvoiceAddressLocal = $project->getProjectInvoiceAddressLocal();
        $this->projectInvoiceAddressEnglish = $project->getProjectInvoiceAddressEnglish();
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
    public function setPurchases(\Doctrine\Common\Collections\Collection|array $purchases): void
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
