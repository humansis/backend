<?php

namespace VoucherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Type as JMS_Type;

/**
 * Smartcard record.
 *
 * Information about products purchased by smartcard.
 *
 * @ORM\Table(name="smartcard_record")
 * @ORM\Entity
 */
class SmartcardRecord
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
     * @var Smartcard
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\Smartcard", inversedBy="records")
     */
    private $smartcard;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\Product")
     */
    private $product;

    /**
     * @var float|null
     *
     * @ORM\Column(name="quantity", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $quantity;

    /**
     * @var float|null
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $value;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @JMS_Type("DateTime<'d-m-Y'>")
     */
    private $createdAt;

    /**
     * @param Smartcard          $smartcard
     * @param Product|null       $product   purchased product
     * @param float|null         $quantity  quantity of product
     * @param float              $value     amount of money
     * @param \DateTimeInterface $createdAt
     */
    public function __construct(
        Smartcard $smartcard,
        ?Product $product,
        ?float $quantity,
        float $value,
        \DateTimeInterface $createdAt
    ) {
        if ($value < 0 && null === $product) {
            throw new \InvalidArgumentException('Product is required for record of purchase.');
        }

        $this->smartcard = $smartcard;
        $this->product = $product;
        $this->quantity = $quantity;
        $this->value = $value;
        $this->createdAt = $createdAt;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return float|null
     */
    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
