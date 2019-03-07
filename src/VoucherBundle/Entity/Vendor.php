<?php

namespace VoucherBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * Vendor
 *
 * @ORM\Table(name="vendor")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\VendorRepository")
 */
class Vendor
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
     * @ORM\Column(name="address", type="string", length=255)
     * @Groups({"FullVendor"})
     */
    private $address;

    /**
     * @var bool
     *
     * @ORM\Column(name="archived", type="boolean")
     * @Groups({"FullVendor"})
     */
    private $archived;

    /**
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\Voucher", mappedBy="vendor", orphanRemoval=true)
     */
    private $vouchers;

    /**
     * @ORM\OneToOne(targetEntity="\UserBundle\Entity\User")
     * @Groups({"FullVendor"})
     */
    private $user;

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
     * Set address.
     *
     * @param string $address
     *
     * @return Vendor
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
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
}
