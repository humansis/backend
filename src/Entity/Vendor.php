<?php

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Smartcard\PreliminaryInvoice;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use Utils\ExportableInterface;

/**
 * Vendor
 *
 * @ORM\Table(name="vendor")
 * @ORM\Entity(repositoryClass="Repository\VendorRepository")
 */
class Vendor
{
    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    #[SymfonyGroups(['FullVendor'])]
    private ?int $id;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    #[SymfonyGroups(['FullVendor'])]
    private string $name;

    /**
     * @ORM\Column(name="shop", type="string", length=255, nullable=true)
     */
    #[SymfonyGroups(['FullVendor'])]
    private string|null $shop = null;

    /**
     * @ORM\Column(name="address_street", type="string", length=255, nullable=true)
     */
    #[SymfonyGroups(['FullVendor'])]
    private string|null $addressStreet;

    /**
     * @ORM\Column(name="address_number", type="string", length=255, nullable=true)
     */
    #[SymfonyGroups(['FullVendor'])]
    private string|null $addressNumber;

    /**
     * @ORM\Column(name="address_postcode", type="string", length=255, nullable=true)
     */
    #[SymfonyGroups(['FullVendor'])]
    private string|null $addressPostcode;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Location")
     */
    #[SymfonyGroups(['FullVendor'])]
    private ?\Entity\Location $location = null;

    /**
     * @ORM\Column(name="archived", type="boolean")
     */
    #[SymfonyGroups(['FullVendor'])]
    private bool $archived;

    /**
     * @ORM\OneToOne(targetEntity="\Entity\User", inversedBy="vendor", cascade={"persist","remove"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    #[SymfonyGroups(['FullVendor'])]
    private $user;

    /**
     * @ORM\Column(name="vendor_no", type="string", nullable=true)
     */
    private string|null $vendorNo = null;

    /**
     * @ORM\Column(name="contract_no", type="string", nullable=true)
     */
    private string|null $contractNo = null;

    /**
     * @ORM\Column(name="can_sell_food", type="boolean")
     */
    private bool $canSellFood = true;

    /**
     * @ORM\Column(name="can_sell_non_food", type="boolean")
     */
    private bool $canSellNonFood = true;

    /**
     * @ORM\Column(name="can_sell_cashback", type="boolean")
     */
    private bool $canSellCashback = true;

    /**
     * @ORM\Column(name="can_do_remote_distributions", type="boolean", nullable=false)
     */
    private bool $canDoRemoteDistributions = false;

    /**
     * @var PreliminaryInvoice[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\Smartcard\PreliminaryInvoice", mappedBy="vendor")
     */
    private $preliminaryInvoices;

    public function __construct()
    {
        $this->archived = false;
        $this->preliminaryInvoices = new ArrayCollection();
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
     * Set name.
     *
     * @param string $name
     *
     * @return Vendor
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set shop.
     *
     * @param string $shop
     *
     * @return Vendor
     */
    public function setShop($shop)
    {
        $this->shop = $shop;

        return $this;
    }

    /**
     * Get shop.
     *
     * @return string|null
     */
    public function getShop()
    {
        return $this->shop;
    }

    /**
     * Set addressStreet.
     *
     * @param string $addressStreet
     *
     * @return Vendor
     */
    public function setAddressStreet($addressStreet)
    {
        $this->addressStreet = $addressStreet;

        return $this;
    }

    /**
     * Get addressStreet.
     *
     * @return string
     */
    public function getAddressStreet()
    {
        return $this->addressStreet;
    }

    /**
     * Set addressNumber.
     *
     * @param string $addressNumber
     *
     * @return Vendor
     */
    public function setAddressNumber($addressNumber)
    {
        $this->addressNumber = $addressNumber;

        return $this;
    }

    /**
     * Get addressNumber.
     *
     * @return string
     */
    public function getAddressNumber()
    {
        return $this->addressNumber;
    }

    /**
     * Set addressPostcode.
     *
     * @param string $addressPostcode
     *
     * @return Vendor
     */
    public function setAddressPostcode($addressPostcode)
    {
        $this->addressPostcode = $addressPostcode;

        return $this;
    }

    /**
     * Get addressPostcode.
     *
     * @return string
     */
    public function getAddressPostcode()
    {
        return $this->addressPostcode;
    }

    /**
     * Set location.
     *
     * @param \Entity\Location|null $location
     *
     * @return Vendor
     */
    public function setLocation(\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return \Entity\Location|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set archived.
     *
     * @param bool $archived
     *
     * @return Vendor
     */
    public function setArchived($archived)
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Get archived.
     *
     * @return bool
     */
    public function getArchived()
    {
        return $this->archived;
    }

    /**
     * Set user.
     *
     * @param User|null $user
     *
     * @return Vendor
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;
        $user->setVendor($this);

        return $this;
    }

    /**
     * Get user.
     *
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }


    public function getVendorNo(): ?string
    {
        return $this->vendorNo;
    }

    public function setVendorNo(?string $vendorNo): self
    {
        $this->vendorNo = $vendorNo;

        return $this;
    }

    public function getContractNo(): ?string
    {
        return $this->contractNo;
    }

    public function setContractNo(?string $contractNo): self
    {
        $this->contractNo = $contractNo;

        return $this;
    }

    public function canSellFood(): bool
    {
        return $this->canSellFood;
    }

    public function setCanSellFood(bool $canSellFood): self
    {
        $this->canSellFood = $canSellFood;

        return $this;
    }

    public function canSellNonFood(): bool
    {
        return $this->canSellNonFood;
    }

    public function setCanSellNonFood(bool $canSellNonFood): self
    {
        $this->canSellNonFood = $canSellNonFood;

        return $this;
    }

    public function canSellCashback(): bool
    {
        return $this->canSellCashback;
    }

    public function setCanSellCashback(bool $canSellCashback): self
    {
        $this->canSellCashback = $canSellCashback;

        return $this;
    }

    public function canDoRemoteDistributions(): bool
    {
        return $this->canDoRemoteDistributions;
    }

    public function setCanDoRemoteDistributions(bool $canDoRemoteDistributions): void
    {
        $this->canDoRemoteDistributions = $canDoRemoteDistributions;
    }

    /**
     * @return Collection|PreliminaryInvoice[]
     */
    public function getPreliminaryInvoices(): Collection
    {
        return $this->preliminaryInvoices;
    }

    /**
     * @param Collection|PreliminaryInvoice[] $preliminaryInvoices
     */
    public function setPreliminaryInvoices($preliminaryInvoices): void
    {
        $this->preliminaryInvoices = $preliminaryInvoices;
    }
}
