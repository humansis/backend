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
     * @var string
     *
     * @ORM\Column(type="string")
     * @ORM\Id
     */
    private $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Entity\Project")
     */
    private $project;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="Entity\Location")
     */
    private $location;

    /**
     * @var AbstractBeneficiary
     *
     * @ORM\ManyToOne(targetEntity="Entity\AbstractBeneficiary")
     */
    private $beneficiary;

    /**
     * @var string
     *
     * @ORM\Column(name="bnf_type", type="string")
     */
    private $beneficiaryType;

    /**
     * @var Assistance
     *
     * @ORM\ManyToOne(targetEntity="Entity\Assistance")
     */
    private $assistance;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Entity\Product")
     */
    private $product;

    /**
     * @var string|null
     *
     * @ORM\Column(name="invoice_number", type="string")
     */
    private $invoiceNumber;

    /**
     * @var Vendor
     *
     * @ORM\ManyToOne(targetEntity="Entity\Vendor")
     */
    private $vendor;

    /**
     * @var Commodity
     *
     * @ORM\ManyToOne(targetEntity="Entity\Commodity")
     */
    private $commodity;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $modalityType;

    /**
     * @var DateTimeInterface|null
     *
     * @ORM\Column(name="date_distribution", type="datetime", nullable=true)
     */
    private $dateDistribution;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="date_purchase", type="datetime")
     */
    private $datePurchase;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $carrierNumber;

    /**
     * @ORM\Column(name="value", type="decimal")
     */
    private $value;

    /**
     * @ORM\Column(name="currency", type="string")
     */
    private $currency;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return Location
     */
    public function getLocation(): Location
    {
        return $this->location;
    }

    /**
     * @return AbstractBeneficiary
     */
    public function getBeneficiary(): AbstractBeneficiary
    {
        return $this->beneficiary;
    }

    /**
     * @return string
     */
    public function getBeneficiaryType(): string
    {
        return $this->beneficiaryType;
    }

    /**
     * @return Assistance
     */
    public function getAssistance(): Assistance
    {
        return $this->assistance;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return string|null
     */
    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    /**
     * @return Vendor
     */
    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    /**
     * @return Commodity
     */
    public function getCommodity(): Commodity
    {
        return $this->commodity;
    }

    /**
     * @return string
     */
    public function getModalityType(): string
    {
        return $this->modalityType;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getDateDistribution(): ?DateTimeInterface
    {
        return $this->dateDistribution;
    }

    /**
     * @return DateTimeInterface
     */
    public function getDatePurchase(): DateTimeInterface
    {
        return $this->datePurchase;
    }

    /**
     * @return string|null
     */
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
