<?php

namespace TransactionBundle\Entity;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\Mapping as ORM;
use VoucherBundle\Entity\Product;

/**
 * Read only entity
 *
 * @ORM\Entity()
 */
class Purchase implements \JsonSerializable
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
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Beneficiary")
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
            'quantity' => $this->quantity,
            'source' => $this->source,
            'usedAt' => $this->usedAt,
        ];
    }
}
