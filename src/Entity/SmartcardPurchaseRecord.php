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
 */
#[ORM\Table(name: 'smartcard_purchase_record')]
#[ORM\Entity]
class SmartcardPurchaseRecord
{
    use StandardizedPrimaryKey;

    #[ORM\ManyToOne(targetEntity: 'Entity\SmartcardPurchase', inversedBy: 'records')]
    #[ORM\JoinColumn(nullable: false)]
    private ?\Entity\SmartcardPurchase $smartcardPurchase = null;

    #[SymfonyGroups(['FullSmartcard'])]
    #[ORM\ManyToOne(targetEntity: 'Entity\Product')]
    #[ORM\JoinColumn(nullable: false)]
    private ?\Entity\Product $product = null;

    /**
     * @var float
     */
    #[SymfonyGroups(['FullSmartcard'])]
    #[ORM\Column(name: 'value', type: 'decimal', precision: 10, scale: 2)]
    private $value;

    #[SymfonyGroups(['FullSmartcard'])]
    #[ORM\Column(name: 'currency', type: 'string', nullable: true)]
    private ?string $currency = null;

    /**
     * @var mixed
     */
    #[SymfonyGroups(['FullSmartcard'])]
    #[ORM\Column(name: 'quantity', type: 'decimal', precision: 10, scale: 2, nullable: true)]
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
