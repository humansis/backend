<?php

namespace InputType;

use Symfony\Component\Validator\Constraints as Assert;

class VoucherPurchase
{
    /**
     * @var array
     *
     * @Assert\Valid()
     * @Assert\NotBlank()
     * @Assert\All({
     *      @Assert\Collection(fields={
     *          "id" = @Assert\Type("int"),
     *          "quantity" = @Assert\Type("numeric"),
     *          "value" = @Assert\Type("numeric")
     *      })
     * })
     */
    private $products;

    /**
     * @var int[]
     *
     * @Assert\Valid()
     * @Assert\NotBlank()
     * @Assert\All({
     *     @Assert\Type("int")
     * })
     */
    private $vouchers;

    /**
     * @var int ID of vendor/seller
     *
     * @Assert\Type("int")
     * @Assert\NotBlank()
     */
    private $vendorId;

    /**
     * @var \DateTimeInterface
     *
     * @Assert\DateTime()
     * @Assert\NotBlank()
     */
    private $createdAt;

    /**
     * @return array
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * @param array $products
     */
    public function setProducts(array $products): void
    {
        $this->products = $products;
    }

    /**
     * @return array
     */
    public function getVouchers(): array
    {
        return $this->vouchers;
    }

    /**
     * @param array $vouchers
     */
    public function setVouchers(array $vouchers): void
    {
        $this->vouchers = $vouchers;
    }

    /**
     * @return int
     */
    public function getVendorId(): int
    {
        return $this->vendorId;
    }

    /**
     * @param int $vendorId
     */
    public function setVendorId(int $vendorId): void
    {
        $this->vendorId = $vendorId;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeInterface $createdAt
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
