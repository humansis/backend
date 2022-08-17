<?php

namespace InputType;

use Symfony\Component\Validator\Constraints as Assert;

class SmartcardPurchase
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
     *          "value" = @Assert\Type("numeric"),
     *          "currency" = {
     *              @Assert\Type("string"),
     *              @Assert\NotBlank(message="Currency can't be empty"),
     *              @Assert\Length(min="3",max="3",allowEmptyString=false)
     *          },
     *      })
     * })
     */
    private $products;

    /**
     * @var int ID of vendor/seller
     *
     * @Assert\Type("int")
     * @Assert\NotBlank()
     */
    private $vendorId;

    /**
     * @var int ID of beneficiary/holder
     *
     * @Assert\Type("int")
     * @ Assert\NotBlank() // will be required later
     */
    private $beneficiaryId;

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
     * @return ?int
     */
    public function getBeneficiaryId(): ?int
    {
        return $this->beneficiaryId;
    }

    /**
     * @param ?int $beneficiaryId
     */
    public function setBeneficiaryId(?int $beneficiaryId): void
    {
        $this->beneficiaryId = $beneficiaryId;
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
