<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
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
    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Entity\SmartcardPurchase", inversedBy="records")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?\Entity\SmartcardPurchase $smartcardPurchase = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Entity\Product")
     * @ORM\JoinColumn(nullable=false)
     *
     */
    #[SymfonyGroups(['FullSmartcard'])]
    private ?\Entity\Product $product = null;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2)
     */
    #[SymfonyGroups(['FullSmartcard'])]
    private $value;

    /**
     * @ORM\Column(name="currency", type="string", nullable=true)
     */
    #[SymfonyGroups(['FullSmartcard'])]
    private ?string $currency = null;

    /**
     * @var mixed
     *
     * @ORM\Column(name="quantity", type="decimal", precision=10, scale=2, nullable=true)
     */
    #[SymfonyGroups(['FullSmartcard'])]
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

    public function getId(): int
    {
        return $this->id;
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

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

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