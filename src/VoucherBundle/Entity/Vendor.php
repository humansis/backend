<?php

namespace VoucherBundle\Entity;

use CommonBundle\Entity\Location;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use CommonBundle\Utils\ExportableInterface;

/**
 * Vendor
 *
 * @ORM\Table(name="vendor")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\VendorRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Vendor extends AbstractEntity implements ExportableInterface
{

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @SymfonyGroups({"FullVendor"})
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="shop", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullVendor"})
     */
    private $shop;

     /**
     * @var string
     *
     * @ORM\Column(name="address_street", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullVendor"})
     */
    private $addressStreet;

    /**
     * @var string
     *
     * @ORM\Column(name="address_number", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullVendor"})
     */
    private $addressNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="address_postcode", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullVendor"})
     */
    private $addressPostcode;

     /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="CommonBundle\Entity\Location")
     *
     * @SymfonyGroups({"FullVendor"})
     */
    private $location;

    /**
     * @var bool
     *
     * @ORM\Column(name="archived", type="boolean")
     * @SymfonyGroups({"FullVendor"})
     */
    private $archived;

    /**
     * @ORM\OneToOne(targetEntity="\UserBundle\Entity\User", inversedBy="vendor", cascade={"persist","remove"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @SymfonyGroups({"FullVendor"})
     */
    private $user;

    /**
     * @var string|null
     *
     * @ORM\Column(name="vendor_no", type="string", nullable=true)
     */
    private $vendorNo;

    /**
     * @var string|null
     *
     * @ORM\Column(name="contract_no", type="string", nullable=true)
     */
    private $contractNo;

    /**
     * @var bool
     *
     * @ORM\Column(name="can_sell_food", type="boolean")
     */
    private $canSellFood = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="can_sell_non_food", type="boolean")
     */
    private $canSellNonFood = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="can_sell_cashback", type="boolean")
     */
    private $canSellCashback = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="can_do_remote_distributions", type="boolean", nullable=false)
     */
    private $canDoRemoteDistributions = false;

    public function __construct()
    {
        $this->archived = false;
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
     * @param \CommonBundle\Entity\Location|null $location
     *
     * @return Vendor
     */
    public function setLocation(\CommonBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return \CommonBundle\Entity\Location|null
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
     * @param \UserBundle\Entity\User|null $user
     *
     * @return Vendor
     */
    public function setUser(\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;
        $user->setVendor($this);

        return $this;
    }

    /**
     * Get user.
     *
     * @return \UserBundle\Entity\User|null
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
            "Vendor's name" => $this->getUser()->getUsername(),
            "Shop's name" => $this->getName(),
            "Shop's type" => $this->getShop(),
            "Address number" => $this->getAddressNumber(),
            "Address street" => $this->getAddressStreet(),
            "Address postcode" => $this->getAddressPostcode(),
            'Contract No.' => $this->getContractNo(),
            'Vendor No.'=> $this->getVendorNo(),
            "adm1" => $adm1,
            "adm2" =>$adm2,
            "adm3" =>$adm3,
            "adm4" =>$adm4,
        ];
    }

    /**
     * @return string|null
     */
    public function getVendorNo(): ?string
    {
        return $this->vendorNo;
    }

    /**
     * @param string|null $vendorNo
     *
     * @return Vendor
     */
    public function setVendorNo(?string $vendorNo): self
    {
        $this->vendorNo = $vendorNo;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getContractNo(): ?string
    {
        return $this->contractNo;
    }

    /**
     * @param string|null $contractNo
     *
     * @return Vendor
     */
    public function setContractNo(?string $contractNo): self
    {
        $this->contractNo = $contractNo;

        return $this;
    }

    /**
     * @return bool
     */
    public function canSellFood(): bool
    {
        return $this->canSellFood;
    }

    /**
     * @param bool $canSellFood
     *
     * @return Vendor
     */
    public function setCanSellFood(bool $canSellFood): self
    {
        $this->canSellFood = $canSellFood;

        return $this;
    }

    /**
     * @return bool
     */
    public function canSellNonFood(): bool
    {
        return $this->canSellNonFood;
    }

    /**
     * @param bool $canSellNonFood
     *
     * @return Vendor
     */
    public function setCanSellNonFood(bool $canSellNonFood): self
    {
        $this->canSellNonFood = $canSellNonFood;

        return $this;
    }

    /**
     * @return bool
     */
    public function canSellCashback(): bool
    {
        return $this->canSellCashback;
    }

    /**
     * @param bool $canSellCashback
     *
     * @return Vendor
     */
    public function setCanSellCashback(bool $canSellCashback): self
    {
        $this->canSellCashback = $canSellCashback;

        return $this;
    }

    /**
     * @return bool
     */
    public function canDoRemoteDistributions(): bool
    {
        return $this->canDoRemoteDistributions;
    }

    /**
     * @param bool $canDoRemoteDistributions
     */
    public function setCanDoRemoteDistributions(bool $canDoRemoteDistributions): void
    {
        $this->canDoRemoteDistributions = $canDoRemoteDistributions;
    }
}
