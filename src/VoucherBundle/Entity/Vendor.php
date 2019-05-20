<?php

namespace VoucherBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use CommonBundle\Utils\ExportableInterface;

/**
 * Vendor
 *
 * @ORM\Table(name="vendor")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\VendorRepository")
 */
class Vendor implements ExportableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullVendor"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Groups({"FullVendor"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="shop", type="string", length=255)
     * @Groups({"FullVendor"})
     */
    private $shop;

     /**
     * @var string
     *
     * @ORM\Column(name="address_street", type="string", length=255, nullable=true)
     * @Groups({"FullVendor"})
     */
    private $addressStreet;

    /**
     * @var string
     *
     * @ORM\Column(name="address_number", type="string", length=255, nullable=true)
     * @Groups({"FullVendor"})
     */
    private $addressNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="address_postcode", type="string", length=255, nullable=true)
     * @Groups({"FullVendor"})
     */
    private $addressPostcode;

     /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="CommonBundle\Entity\Location")
     *
     * @Groups({"FullVendor"})
     */
    private $location;

    /**
     * @var bool
     *
     * @ORM\Column(name="archived", type="boolean")
     * @Groups({"FullVendor"})
     */
    private $archived;

    /**
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\Voucher", mappedBy="vendor", orphanRemoval=true)
     * @ORM\OrderBy({"usedAt" = "DESC"})
     */
    private $vouchers;

    /**
     * @ORM\OneToOne(targetEntity="\UserBundle\Entity\User", inversedBy="vendor", cascade={"persist","remove"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Groups({"FullVendor"})
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="country_iso3", type="string", length=45)
     * @Groups({"FullVendor"})
     */
    private $countryIso3;


    public function __construct()
    {
        $this->vouchers = new ArrayCollection();
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
     * @return string
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
     * @return Collection|Voucher[]
     */
    public function getVouchers() : Collection
    {
        return $this->vouchers;
    }

    public function addVoucher(Voucher $voucher) : self
    {
        if (!$this->vouchers->contains($voucher)) {
            $this->vouchers[] = $voucher;
            $voucher->setVendor($this);
        }

        return $this;
    }

    public function removeVoucher(Voucher $voucher) : self
    {
        if ($this->vouchers->contains($voucher)) {
            $this->vouchers->removeElement($voucher);
            // set the owning side to null (unless already changed)
            if ($voucher->getVendor() === $this) {
                $voucher->setVendor(null);
            }
        }

        return $this;
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
            "adm1" => $adm1,
            "adm2" =>$adm2,
            "adm3" =>$adm3,
            "adm4" =>$adm4,
        ];
    }

    /**
     * Set countryIso3.
     *
     * @param string $countryIso3
     *
     * @return Vendor
     */
    public function setCountryIso3($countryIso3)
    {
        $this->countryIso3 = $countryIso3;

        return $this;
    }

    /**
     * Get countryIso3.
     *
     * @return string
     */
    public function getCountryIso3()
    {
        return $this->countryIso3;
    }
}
