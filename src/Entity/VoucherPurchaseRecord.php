<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Voucher Purchase Record.
 *
 * @ORM\Table(name="voucher_purchase_record")
 * @ORM\Entity(repositoryClass="Repository\VoucherPurchaseRecordRepository")
 */
class VoucherPurchaseRecord
{
    use StandardizedPrimaryKey;

    /**
     * @var VoucherPurchase
     *
     * @ORM\ManyToOne(targetEntity="Entity\VoucherPurchase", inversedBy="records")
     * @ORM\JoinColumn(nullable=false)
     */
    private $voucherPurchase;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Entity\Product")
     * @ORM\JoinColumn(nullable=false)
     *
     * @SymfonyGroups({"FullVoucher", "ValidatedAssistance"})
     */
    private $product;

    /**
     * @var mixed
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2, nullable=true)
     * @SymfonyGroups({"FullVoucher", "FullBooklet", "ValidatedAssistance"})
     */
    private $value;

    /**
     * @var mixed
     *
     * @ORM\Column(name="quantity", type="decimal", precision=10, scale=2, nullable=true)
     * @SymfonyGroups({"FullVoucher", "FullBooklet", "ValidatedAssistance"})
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
