<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\Household;
use NewApiBundle\Entity\Location;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Project;
use NewApiBundle\Entity\Assistance;
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
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Project")
     */
    private $project;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Location")
     */
    private $location;

    /**
     * @var Beneficiary
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Beneficiary")
     */
    private $beneficiary;

    /**
     * @var Household
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Household")
     */
    private $household;

    /**
     * @var Assistance
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Assistance")
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
