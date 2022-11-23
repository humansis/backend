<?php

namespace InputType;

use DateTime;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints\Iso8601;

class VoucherPurchase
{
    /**
     * @Assert\All({
     *      @Assert\Collection(fields={
     *          "id" = @Assert\Type("int"),
     *          "quantity" = @Assert\Type("numeric"),
     *          "value" = @Assert\Type("numeric")
     *      })
     * })
     */
    #[Assert\Valid]
    #[Assert\NotBlank]
    private ?array $products = null;

    /**
     * @var int[]
     *
     * @Assert\All({
     *     @Assert\Type("int")
     * })
     */
    #[Assert\Valid]
    #[Assert\NotBlank]
    private $vouchers;

    /**
     * @var int ID of vendor/seller
     */
    #[Assert\Type('int')]
    #[Assert\NotBlank]
    private ?int $vendorId = null;

    /**
     * @Iso8601
     */
    #[Assert\NotBlank]
    private ?DateTime $createdAt = null;

    public function getProducts(): array
    {
        return $this->products;
    }

    public function setProducts(array $products): void
    {
        $this->products = $products;
    }

    public function getVouchers(): array
    {
        return $this->vouchers;
    }

    public function setVouchers(array $vouchers): void
    {
        $this->vouchers = $vouchers;
    }

    public function getVendorId(): int
    {
        return $this->vendorId;
    }

    public function setVendorId(int $vendorId): void
    {
        $this->vendorId = $vendorId;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
