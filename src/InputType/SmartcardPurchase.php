<?php

namespace InputType;

use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints\Iso8601;

class SmartcardPurchase
{
    /**
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
    #[Assert\Valid]
    #[Assert\NotBlank]
    private ?array $products = null;

    /**
     * @var int ID of vendor/seller
     */
    #[Assert\Type('int')]
    #[Assert\NotBlank]
    private ?int $vendorId = null;

    /**
     * @var int ID of beneficiary/holder
     */
    #[Assert\Type('int')]
    private ?int $beneficiaryId = null;

    /**
     * @Iso8601
     */
    #[Assert\NotBlank]
    private ?\DateTime $createdAt = null;

    public function getProducts(): array
    {
        return $this->products;
    }

    public function setProducts(array $products): void
    {
        $this->products = $products;
    }

    public function getVendorId(): int
    {
        return $this->vendorId;
    }

    public function setVendorId(int $vendorId): void
    {
        $this->vendorId = $vendorId;
    }

    public function getBeneficiaryId(): ?int
    {
        return $this->beneficiaryId;
    }

    public function setBeneficiaryId(?int $beneficiaryId): void
    {
        $this->beneficiaryId = $beneficiaryId;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
