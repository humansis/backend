<?php

namespace VoucherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;



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
     *
     */
    private $product;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2)
     *
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", nullable=true)
     *
     */
    private $currency;

    /**
     * @var mixed
     *
     * @ORM\Column(name="quantity", type="decimal", precision=10, scale=2, nullable=true)
     *
     */
    private $quantity;

    public static function create(SmartcardPurchase $purchase, Product $product, $quantity, $value, ?string $currency)
    {
        $entity = new self();
        $entity->smartcardPurchase = $purchase;
        $entity->product = $product;
        $entity->quantity = $quantity;
        $entity->value = $value;
        $entity->currency = $currency;

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
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        if (null !== $this->currency) {
            throw new \LogicException('Unable to change currency in purchase record #'.$this->id);
        }

        $this->currency = $currency;
    }

    /**
     * @return mixed
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
}
