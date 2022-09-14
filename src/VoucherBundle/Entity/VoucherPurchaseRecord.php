<?php

namespace VoucherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;


/**
 * Voucher Purchase Record.
 *
 * @ORM\Table(name="voucher_purchase_record")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\VoucherPurchaseRecordRepository")
 */
class VoucherPurchaseRecord extends AbstractEntity
{
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
