<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Voucher Purchase Record.
 */
#[ORM\Table(name: 'voucher_purchase_record')]
#[ORM\Entity(repositoryClass: 'Repository\VoucherPurchaseRecordRepository')]
class VoucherPurchaseRecord
{
    use StandardizedPrimaryKey;

    #[ORM\ManyToOne(targetEntity: 'Entity\VoucherPurchase', inversedBy: 'records')]
    #[ORM\JoinColumn(nullable: false)]
    private ?\Entity\VoucherPurchase $voucherPurchase = null;

    #[SymfonyGroups(['FullVoucher', 'ValidatedAssistance'])]
    #[ORM\ManyToOne(targetEntity: 'Entity\Product')]
    #[ORM\JoinColumn(nullable: false)]
    private ?\Entity\Product $product = null;

    /**
     * @var mixed
     */
    #[SymfonyGroups(['FullVoucher', 'FullBooklet', 'ValidatedAssistance'])]
    #[ORM\Column(name: 'value', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private $value;

    /**
     * @var mixed
     */
    #[SymfonyGroups(['FullVoucher', 'FullBooklet', 'ValidatedAssistance'])]
    #[ORM\Column(name: 'quantity', type: 'decimal', precision: 10, scale: 2, nullable: true)]
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
