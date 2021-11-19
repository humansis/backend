<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use DateTimeInterface;
use NewApiBundle\Request\InputTypeInterface;
use NewApiBundle\Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

class SmartcardPurchaseInputType implements InputTypeInterface
{
    /**
     * @var PurchaseProductInputType[]
     *
     * @Assert\Type("array")
     * @Assert\Valid
     * @Assert\NotBlank
     */
    private $products = [];

    /**
     * @var DateTimeInterface
     *
     * @Iso8601
     * @Assert\NotNull
     * @Assert\NotBlank
     */
    private $createdAt;

    /**
     * @var int
     *
     * @Assert\NotNull
     * @Assert\Type("integer")
     * @Assert\GreaterThan(0)
     */
    private $vendorId;

    /**
     * @var int
     *
     * @Assert\NotNull
     * @Assert\Type("integer")
     * @Assert\GreaterThan(0)
     */
    private $beneficiaryId;

    /**
     * @var int
     *
     * @Assert\NotNull
     * @Assert\Type("integer")
     * @Assert\GreaterThan(0)
     */
    private $assistanceId;

    /**
     * @var float|int|string|null
     *
     * @Assert\Type(type="numeric")
     */
    private $balanceBefore;

    /**
     * @var float|int|string|null
     *
     * @Assert\Type(type="numeric")
     */
    private $balanceAfter;

    /**
     * @return PurchaseProductInputType[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param PurchaseProductInputType $purchaseProduct
     */
    public function addProduct(PurchaseProductInputType $purchaseProduct)
    {
        $this->products[] = $purchaseProduct;
    }

    /**
     * @param PurchaseProductInputType $purchaseProduct
     */
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

    /**
     * @return int
     */
    public function getAssistanceId(): int
    {
        return $this->assistanceId;
    }

    /**
     * @param int $assistanceId
     */
    public function setAssistanceId(int $assistanceId): void
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

    /**
     * @param float|int|string $balanceBefore
     */
    public function setBalanceBefore($balanceBefore)
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

    /**
     * @param float|int|string $balanceAfter
     */
    public function setBalanceAfter($balanceAfter): void
    {
        $this->balanceAfter = $balanceAfter;
    }
}
