<?php

declare(strict_types=1);

namespace Entity;

use DateTimeInterface;
use Entity\AbstractBeneficiary;
use Entity\Location;
use Entity\Assistance;
use Entity\Commodity;
use Doctrine\ORM\Mapping as ORM;
use Entity\Project;
use Entity\Product;
use Entity\Vendor;

/**
 * Read only entity.
 *
 * @ORM\MappedSuperclass(repositoryClass="Repository\PurchasedItemRepository")
 * @ORM\Table(name="view_purchased_item")
 */
class PurchasedItem
{
    /**
     *
     * @ORM\Column(type="string")
     * @ORM\Id
     */
    private string $id;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Project")
     */
    private \Entity\Project $project;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Location")
     */
    private \Entity\Location $location;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\AbstractBeneficiary")
     */
    private \Entity\AbstractBeneficiary $beneficiary;

    /**
     * @ORM\Column(name="bnf_type", type="string")
     */
    private string $beneficiaryType;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Assistance")
     */
    private \Entity\Assistance $assistance;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Product")
     */
    private \Entity\Product $product;

    /**
     * @ORM\Column(name="invoice_number", type="string")
     */
    private ?string $invoiceNumber = null;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Vendor")
     */
    private \Entity\Vendor $vendor;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Commodity")
     */
    private \Entity\Commodity $commodity;

    /**
     * @ORM\Column(type="string")
     */
    private string $modalityType;

    /**
     * @ORM\Column(name="date_distribution", type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $dateDistribution = null;

    /**
     * @ORM\Column(name="date_purchase", type="datetime")
     */
    private \DateTimeInterface $datePurchase;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $carrierNumber = null;

    /**
     * @ORM\Column(name="value", type="decimal")
     */
    private $value;

    /**
     * @ORM\Column(name="currency", type="string")
     */
    private $currency;

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

    public function getBeneficiary(): AbstractBeneficiary
    {
        return $this->beneficiary;
    }

    public function getBeneficiaryType(): string
    {
        return $this->beneficiaryType;
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

    public function getCommodity(): Commodity
    {
        return $this->commodity;
    }

    public function getModalityType(): string
    {
        return $this->modalityType;
    }

    public function getDateDistribution(): ?DateTimeInterface
    {
        return $this->dateDistribution;
    }

    public function getDatePurchase(): DateTimeInterface
    {
        return $this->datePurchase;
    }

    public function getCarrierNumber(): ?string
    {
        return $this->carrierNumber;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }
}
