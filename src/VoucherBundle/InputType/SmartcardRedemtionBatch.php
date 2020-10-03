<?php

namespace VoucherBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

class SmartcardRedemtionBatch
{
    /**
     * @var int[]
     *
     * @Assert\Valid()
     * @Assert\NotBlank()
     * @Assert\All({
     *     @Assert\Type("int")
     * })
     */
    private $purchases;

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
    private $redeemedAt;

    /**
     * @return array
     */
    public function getPurchases(): array
    {
        return $this->purchases;
    }

    /**
     * @param array $purchases
     */
    public function setPurchases(array $purchases): void
    {
        $this->purchases = $purchases;
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
    public function getRedeemedAt(): \DateTimeInterface
    {
        return $this->redeemedAt;
    }

    /**
     * @param \DateTimeInterface $redeemedAt
     */
    public function setRedeemedAt(\DateTimeInterface $redeemedAt): void
    {
        $this->redeemedAt = $redeemedAt;
    }
}
