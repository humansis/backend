<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;
use LogicException;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

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
    use StandardizedPrimaryKey;

    /**
     * @var SmartcardPurchase
     *
     * @ORM\ManyToOne(targetEntity="Entity\SmartcardPurchase", inversedBy="records")
     * @ORM\JoinColumn(nullable=false)
     */
    private $smartcardPurchase;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Entity\Product")
     * @ORM\JoinColumn(nullable=false)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $product;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2)
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", nullable=true)
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $currency;

    /**
     * @var mixed
     *
     * @ORM\Column(name="quantity", type="decimal", precision=10, scale=2, nullable=true)
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $quantity;

    public static function create(
        SmartcardPurchase $purchase,
        Product $product,
        $quantity,
        $value,
        ?string $currency
    ): SmartcardPurchaseRecord {
        $entity = new self();
        $entity->smartcardPurchase = $purchase;
        $entity->product = $product;
        $entity->quantity = $quantity;
        $entity->value = $value;
        $entity->currency = $currency;

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
            throw new LogicException('Unable to change currency in purchase record #' . $this->id);
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
