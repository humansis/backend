<?php

namespace Entity;

use Entity\Location;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use Utils\ExportableInterface;

/**
 * Vendor
 *
 * @ORM\Table(name="vendor")
 * @ORM\Entity(repositoryClass="Repository\VendorRepository")
 */
class Vendor implements ExportableInterface
{
    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    #[SymfonyGroups(['FullVendor'])]
    private int $id;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    #[SymfonyGroups(['FullVendor'])]
    private string $name;

    /**
     * @ORM\Column(name="shop", type="string", length=255, nullable=true)
     */
    #[SymfonyGroups(['FullVendor'])]
    private ?string $shop = null;

    /**
     * @ORM\Column(name="address_street", type="string", length=255, nullable=true)
     */
    #[SymfonyGroups(['FullVendor'])]
    private string $addressStreet;

    /**
     * @ORM\Column(name="address_number", type="string", length=255, nullable=true)
     */
    #[SymfonyGroups(['FullVendor'])]
    private string $addressNumber;

    /**
     * @ORM\Column(name="address_postcode", type="string", length=255, nullable=true)
     */
    #[SymfonyGroups(['FullVendor'])]
    private string $addressPostcode;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Entity\Location")
     *
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
    private ?string $vendorNo = null;

    /**
     * @ORM\Column(name="contract_no", type="string", nullable=true)
     */
    private ?string $contractNo = null;

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

    public function __construct()
    {
        $this->archived = false;
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

    public function getMappedValueForExport(): array
    {
        $adm1 = $this->getLocation() ? $this->getLocation()->getAdm1Name() : null;
        $adm2 = $this->getLocation() ? $this->getLocation()->getAdm2Name() : null;
        $adm3 = $this->getLocation() ? $this->getLocation()->getAdm3Name() : null;
        $adm4 = $this->getLocation() ? $this->getLocation()->getAdm4Name() : null;

        return [
            "Vendor's name" => $this->getUser()->getUserIdentifier(),
            "Shop's name" => $this->getName(),
            "Shop's type" => $this->getShop(),
            "Address number" => $this->getAddressNumber(),
            "Address street" => $this->getAddressStreet(),
            "Address postcode" => $this->getAddressPostcode(),
            'Contract No.' => $this->getContractNo(),
            'Vendor No.' => $this->getVendorNo(),
            "adm1" => $adm1,
            "adm2" => $adm2,
            "adm3" => $adm3,
            "adm4" => $adm4,
        ];
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
}