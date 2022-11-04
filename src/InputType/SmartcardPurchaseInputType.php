<?php

declare(strict_types=1);

namespace InputType;

use DateTimeInterface;
use Request\InputTypeInterface;
use Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

class SmartcardPurchaseInputType implements InputTypeInterface
{
    /**
     * @var PurchaseProductInputType[]
     */
    #[Assert\Type('array')]
    #[Assert\Valid]
    #[Assert\NotBlank]
    private array $products = [];

    /**
     * @Iso8601
     */
    #[Assert\NotNull]
    #[Assert\NotBlank]
    private ?\DateTime $createdAt = null;

    #[Assert\NotNull]
    #[Assert\Type('integer')]
    #[Assert\GreaterThan(0)]
    private ?int $vendorId = null;

    #[Assert\NotNull]
    #[Assert\Type('integer')]
    #[Assert\GreaterThan(0)]
    private ?int $beneficiaryId = null;

    #[Assert\NotNull]
    #[Assert\Type('integer')]
    #[Assert\GreaterThan(0)]
    private ?int $assistanceId = null;

    #[Assert\Type(type: 'numeric')]
    private float|int|string|null $balanceBefore = null;

    #[Assert\Type(type: 'numeric')]
    private float|int|string|null $balanceAfter = null;

    /**
     * @return PurchaseProductInputType[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    public function addProduct(PurchaseProductInputType $purchaseProduct)
    {
        $this->products[] = $purchaseProduct;
    }

    public function removeProduct(PurchaseProductInputType $purchaseProduct)
    {
        // method must be declared to fullfill normalizer requirements
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param string $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return int
     */
    public function getVendorId()
    {
        return $this->vendorId;
    }

    /**
     * @param int $vendorId
     */
    public function setVendorId($vendorId)
    {
        $this->vendorId = $vendorId;
    }

    /**
     * @return int
     */
    public function getBeneficiaryId()
    {
        return $this->beneficiaryId;
    }

    /**
     * @param int $beneficiaryId
     */
    public function setBeneficiaryId($beneficiaryId)
    {
        $this->beneficiaryId = $beneficiaryId;
    }

    public function getAssistanceId(): ?int
    {
        return $this->assistanceId;
    }

    public function setAssistanceId(?int $assistanceId): void
    {
        $this->assistanceId = $assistanceId;
    }

    /**
     * @return float|int|string|null
     */
    public function getBalanceBefore()
    {
        return $this->balanceBefore;
    }

    public function setBalanceBefore(float|int|string $balanceBefore)
    {
        $this->balanceBefore = $balanceBefore;
    }

    /**
     * @return float|int|string|null
     */
    public function getBalanceAfter()
    {
        return $this->balanceAfter;
    }

    public function setBalanceAfter(float|int|string $balanceAfter): void
    {
        $this->balanceAfter = $balanceAfter;
    }
}
