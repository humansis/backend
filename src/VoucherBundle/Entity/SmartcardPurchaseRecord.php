<?php

namespace VoucherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Type as JMS_Type;

/**
 * Smartcard purchase record.
 *
 * Information about products purchased by smartcard.
 *
 * @ORM\Table(name="smartcard_purchase_record")
 * @ORM\Entity
 */
class SmartcardPurchaseRecord
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
     * @var SmartcardPurchase
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\SmartcardPurchase", inversedBy="records")
     * @ORM\JoinColumn(nullable=false)
     */
    private $smartcardPurchase;
    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\Product")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Groups({"FullSmartcard"})
     */
    private $product;

    /**
     * @var mixed
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"FullSmartcard"})
     */
    private $value;

    /**
     * @var mixed
     *
     * @ORM\Column(name="quantity", type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"FullSmartcard"})
     */
    private $quantity;

    public static function create(SmartcardPurchase $purchase, Product $product, $quantity, $value)
    {
        $entity = new self();
        $entity->smartcardPurchase = $purchase;
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
