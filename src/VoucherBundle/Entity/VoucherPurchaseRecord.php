<?php

namespace VoucherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Type as JMS_Type;

/**
 * Voucher Purchase Record.
 *
 * @ORM\Table(name="voucher_purchase_record",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"voucher_purchase_id", "product_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\VoucherPurchaseRecordRepository")
 */
class VoucherPurchaseRecord
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
     * @var VoucherPurchase
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\VoucherPurchase", inversedBy="records")
     * @ORM\JoinColumn(nullable=false)
     */
    private $voucherPurchase;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\Product")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Groups({"FullVoucher", "ValidatedDistribution"})
     */
    private $product;

    /**
     * @var mixed
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"FullVoucher", "FullBooklet", "ValidatedDistribution"})
     */
    private $value;

    /**
     * @var mixed
     *
     * @ORM\Column(name="quantity", type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"FullVoucher", "FullBooklet", "ValidatedDistribution"})
     */
    private $quantity;

    public static function create(VoucherPurchase $purchase, Product $product, $quantity, $value)
    {
        $entity = new self();
        $entity->voucherPurchase = $purchase;
        $entity->product = $product;
        $entity->quantity = $quantity;
        $entity->value = $value;

        return $entity;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
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
    public function getQuantity()
    {
        return $this->quantity;
    }
}
