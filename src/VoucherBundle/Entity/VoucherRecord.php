<?php

namespace VoucherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Type as JMS_Type;

/**
 * Voucher Record.
 *
 * @ORM\Table(name="voucher_record",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"voucher_id", "product_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\VoucherRecordRepository")
 */
class VoucherRecord
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullVoucher"})
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="used_at", type="datetime", nullable=true)
     * @JMS_Type("DateTime<'d-m-Y'>")
     * @Groups({"FullVoucher", "ValidatedDistribution"})
     */
    private $usedAt;

    /**
     * @var Voucher
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\Voucher", inversedBy="records")
     */
    private $voucher;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\Product")
     * @Groups({"FullVoucher", "ValidatedDistribution"})
     */
    private $product;

    /**
     * @var mixed
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"FullVoucher", "FullBooklet", "ValidatedDistribution"})
     */
    private $value;

    /**
     * @var mixed
     *
     * @ORM\Column(name="quantity", type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"FullVoucher", "FullBooklet", "ValidatedDistribution"})
     */
    private $quantity;

    public static function create(Product $product, $quantity, $value, ?\DateTime $usedAt = null)
    {
        $entity = new VoucherRecord();
        $entity->setProduct($product);
        $entity->setQuantity($quantity);
        $entity->setValue($value);
        $entity->setUsedAt($usedAt);

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
     * @return \DateTime|null
     */
    public function getUsedAt(): ?\DateTime
    {
        return $this->usedAt;
    }

    /**
     * @param \DateTime|null $usedAt
     */
    public function setUsedAt(?\DateTime $usedAt): void
    {
        $this->usedAt = $usedAt;
    }

    /**
     * @return Voucher
     */
    public function getVoucher(): Voucher
    {
        return $this->voucher;
    }

    /**
     * @param Voucher $voucher
     */
    public function setVoucher(Voucher $voucher): void
    {
        $this->voucher = $voucher;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param mixed $quantity
     */
    public function setQuantity($quantity): void
    {
        $this->quantity = $quantity;
    }
}
