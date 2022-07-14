<?php

namespace TransactionBundle\Entity;

use NewApiBundle\Entity\Beneficiary;
use Doctrine\ORM\Mapping as ORM;
use VoucherBundle\Entity\Product;

/**
 * Read only entity.
 *
 * @ORM\MappedSuperclass(repositoryClass="TransactionBundle\Repository\PurchasedItemRepository")
 */
class PurchasedItem implements \JsonSerializable
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    private $id;

    /**
     * @var Beneficiary
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Beneficiary")
     */
    private $beneficiary;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\Product")
     */
    private $product;

    /**
     * @ORM\Column(name="value", type="decimal")
     */
    private $value;

    /**
     * @ORM\Column(name="currency", type="string")
     */
    private $currency;

    /**
     * @ORM\Column(name="quantity", type="decimal")
     */
    private $quantity;

    /**
     * @ORM\Column(name="source", type="string")
     */
    private $source;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="used_at", type="datetime")
     */
    private $usedAt;

    public function getBeneficiary(): Beneficiary
    {
        return $this->beneficiary;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getQuantity()
    {
        return $this->quantity;

    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getUsedAt(): \DateTimeInterface
    {
        return $this->usedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'beneficiary' => [
                'id' => $this->beneficiary->getId(),
                'name' => $this->beneficiary->getLocalGivenName().' '.$this->beneficiary->getLocalFamilyName(),
            ],
            'productId' => $this->product->getId(),
            'productName' => $this->product->getName(),
            'unit' => $this->product->getUnit(),
            'value' => $this->value,
            'currency' => $this->currency,
            'quantity' => $this->quantity,
            'source' => $this->source,
            'usedAt' => $this->usedAt,
        ];
    }
}
