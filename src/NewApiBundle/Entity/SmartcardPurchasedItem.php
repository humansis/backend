<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use CommonBundle\Entity\Location;
use Doctrine\ORM\Mapping as ORM;
use ProjectBundle\Entity\Project;
use DistributionBundle\Entity\Assistance;
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Vendor;

/**
 * Read only entity.
 *
 * @ORM\MappedSuperclass(repositoryClass="NewApiBundle\Repository\SmartcardPurchasedItemRepository")
 * @ORM\Table(name="view_smartcard_purchased_item")
 */
class SmartcardPurchasedItem
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
     * @ORM\ManyToOne(targetEntity="ProjectBundle\Entity\Project")
     */
    private $project;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="CommonBundle\Entity\Location")
     */
    private $location;

    /**
     * @var Beneficiary
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Beneficiary")
     */
    private $beneficiary;

    /**
     * @var Household
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Household")
     */
    private $household;

    /**
     * @var Assistance
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\Assistance")
     */
    private $assistance;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\Product")
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
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\Vendor")
     */
    private $vendor;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="date_distribution", type="datetime", nullable=true)
     */
    private $dateDistribution;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="date_purchase", type="datetime")
     */
    private $datePurchase;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $smartcardCode;

    /**
     * @ORM\Column(name="value", type="decimal")
     */
    private $value;

    /**
     * @ORM\Column(name="currency", type="string")
     */
    private $currency;

    /**
     * @ORM\Column(name="id_number", type="string")
     */
    private $idNumber;

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
     * @return Beneficiary
     */
    public function getBeneficiary(): Beneficiary
    {
        return $this->beneficiary;
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
     * @return \DateTimeInterface|null
     */
    public function getDateDistribution(): ?\DateTimeInterface
    {
        return $this->dateDistribution;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDatePurchase(): \DateTimeInterface
    {
        return $this->datePurchase;
    }

    /**
     * @return string|null
     */
    public function getSmartcardCode(): ?string
    {
        return $this->smartcardCode;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @return Household
     */
    public function getHousehold(): Household
    {
        return $this->household;
    }

    /**
     * @return string|null
     */
    public function getIdNumber(): ?string
    {
        return $this->idNumber;
    }
}
