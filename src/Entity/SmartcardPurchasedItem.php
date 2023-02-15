<?php

declare(strict_types=1);

namespace Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Read only entity.
 */
#[ORM\Table(name: 'view_smartcard_purchased_item')]
#[ORM\Entity(repositoryClass: 'Repository\SmartcardPurchasedItemRepository', readOnly: true)]
class SmartcardPurchasedItem
{
    #[ORM\Column(type: 'string')]
    #[ORM\Id]
    private string $id;

    #[ORM\ManyToOne(targetEntity: 'Entity\Project')]
    private \Entity\Project $project;

    #[ORM\ManyToOne(targetEntity: 'Entity\Location')]
    private \Entity\Location $location;

    #[ORM\ManyToOne(targetEntity: 'Entity\Beneficiary')]
    private \Entity\Beneficiary $beneficiary;

    #[ORM\ManyToOne(targetEntity: 'Entity\Household')]
    private \Entity\Household $household;

    #[ORM\ManyToOne(targetEntity: 'Entity\Assistance')]
    private \Entity\Assistance $assistance;

    #[ORM\ManyToOne(targetEntity: 'Entity\Product')]
    private \Entity\Product $product;

    #[ORM\Column(name: 'invoice_number', type: 'string')]
    private ?string $invoiceNumber = null;

    #[ORM\ManyToOne(targetEntity: 'Entity\Vendor')]
    private \Entity\Vendor $vendor;

    #[ORM\Column(name: 'date_purchase', type: 'datetime')]
    private \DateTimeInterface $datePurchase;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $smartcardCode = null;

    #[ORM\Column(name: 'value', type: 'decimal')]
    private $value;

    #[ORM\Column(name: 'currency', type: 'string')]
    private $currency;

    #[ORM\Column(name: 'id_number', type: 'string')]
    private $idNumber;

    public function getId(): string
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getBeneficiary(): Beneficiary
    {
        return $this->beneficiary;
    }

    public function getAssistance(): Assistance
    {
        return $this->assistance;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    public function getDatePurchase(): DateTimeInterface
    {
        return $this->datePurchase;
    }

    public function getSmartcardCode(): ?string
    {
        return $this->smartcardCode;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getHousehold(): Household
    {
        return $this->household;
    }

    public function getIdNumber(): ?string
    {
        return $this->idNumber;
    }
}
