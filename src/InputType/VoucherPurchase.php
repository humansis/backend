<?php

namespace InputType;

use DateTime;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints\Iso8601;

class VoucherPurchase
{
    #[Assert\All([
        new Assert\Collection(
            fields: [
                'id' => new Assert\Type('int'),
                'quantity' => new Assert\Type('numeric'),
                'value' => new Assert\Type('numeric')
            ]
        )
    ])]
    #[Assert\Valid]
    #[Assert\NotBlank]
    private ?array $products = null;

    /**
     * @var int[]
     */
    #[Assert\All([new Assert\Type('int')])]
    #[Assert\Valid]
    #[Assert\NotBlank]
    private $vouchers;

    /**
     * @var int ID of vendor/seller
     */
    #[Assert\Type('int')]
    #[Assert\NotBlank]
    private ?int $vendorId = null;

    #[Assert\NotBlank]
    #[Iso8601]
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
