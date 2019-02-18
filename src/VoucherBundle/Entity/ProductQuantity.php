<?php

namespace VoucherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use \VoucherBundle\Entity\Product;
use \VoucherBundle\Entity\Voucher;


/**
 * ProductQuantity
 *
 * @ORM\Table(name="product_quantity")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\ProductQuantityRepository")
 */
class ProductQuantity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="\VoucherBundle\Entity\Product", inversedBy="productQuantities")
     * @ORM\JoinColumn(nullable=true)
     */
    private $product;

    /**
     * @var Voucher
     *
     * @ORM\ManyToOne(targetEntity="\VoucherBundle\Entity\Voucher", inversedBy="productQuantities")
     * @ORM\JoinColumn(nullable=true)
     */
    private $voucher;

    /**
     * @var int
     *
     * @ORM\Column(name="quantity", type="integer")
     */
    private $quantity;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float")
     */
    private $price;


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
     * Set product.
     *
     * @param int $product
     *
     * @return ProductQuantity
     */
    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product.
     *
     * @return int
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set voucher.
     *
     * @param int $voucher
     *
     * @return ProductQuantity
     */
    public function setVoucher($voucher)
    {
        $this->voucher = $voucher;

        return $this;
    }

    /**
     * Get voucher.
     *
     * @return int
     */
    public function getVoucher()
    {
        return $this->voucher;
    }

    /**
     * Set quantity.
     *
     * @param int $quantity
     *
     * @return ProductQuantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity.
     *
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set price.
     *
     * @param float $price
     *
     * @return ProductQuantity
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price.
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }
}
