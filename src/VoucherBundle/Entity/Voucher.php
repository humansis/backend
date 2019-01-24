<?php

namespace VoucherBundle\Entity;

use DistributionBundle\Entity\DistributionBeneficiary;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use \VoucherBundle\Entity\Product;
use \VoucherBundle\Entity\Booklet;
use \VoucherBundle\Entity\Vendor;
use JMS\Serializer\Annotation\Groups;

/**
 * Voucher
 *
 * @ORM\Table(name="voucher")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\VoucherRepository")
 */
class Voucher
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullVoucher"})
     */
    private $id;

    /**
     * @var bool
     *
     * @ORM\Column(name="used", type="boolean")
     * @Groups({"FullVoucher"})
     */
    private $used;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     * @Groups({"FullVoucher"})
     */
    private $code;

    /**
     * @ORM\ManyToMany(targetEntity="\VoucherBundle\Entity\Product", inversedBy="vouchers")
     * @Groups({"FullVoucher"})
     */
    private $product;

    /**
     * @var int
     *
     * @ORM\Column(name="individual_value", type="integer")
     * @Groups({"FullVoucher"})
     */
    private $individualValue;

    /**
     * @ORM\ManyToOne(targetEntity="\VoucherBundle\Entity\Booklet", inversedBy="vouchers")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"FullVoucher"})
     */
    private $booklet;

    /**
     * @ORM\ManyToOne(targetEntity="\VoucherBundle\Entity\Vendor", inversedBy="vouchers")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"FullVoucher"})
     */
    private $vendor;

    public function __construct()
    {
        $this->product = new ArrayCollection();
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
     * Set used.
     *
     * @param bool $used
     *
     * @return Voucher
     */
    public function setUsed($used)
    {
        $this->used = $used;

        return $this;
    }

    /**
     * Get used.
     *
     * @return bool
     */
    public function getUsed()
    {
        return $this->used;
    }

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return Voucher
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return Collection|Product[]
     */
    public function getProduct(): Collection
    {
        return $this->product;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->product->contains($product)) {
            $this->product[] = $product;
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->product->contains($product)) {
            $this->product->removeElement($product);
        }

        return $this;
    }

    public function getBooklet(): Booklet
    {
        return $this->booklet;
    }

    public function setBooklet(Booklet $booklet): self
    {
        $this->booklet = $booklet;

        return $this;
    }

    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    public function setVendor(Vendor $vendor): self
    {
        $this->vendor = $vendor;

        return $this;
    }

    public function getDistributionBeneficiary(): DistributionBeneficiary
    {
        return $this->distribution_beneficiary;
    }

    public function setDistributionBeneficiary(DistributionBeneficiary $distribution_beneficiary): self
    {
        $this->distribution_beneficiary = $distribution_beneficiary;

        return $this;
    }
}
