<?php

namespace Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * Class VoucherRedemptionBatch.
 *
 * @ORM\Table(name="voucher_redemption_batch")
 * @ORM\Entity(repositoryClass="\Repository\VoucherRedemptionBatchRepository")
 */
class VoucherRedemptionBatch
{
    use StandardizedPrimaryKey;

    /**
     * @var Vendor
     *
     * @ORM\ManyToOne(targetEntity="\Entity\Vendor")
     * @ORM\JoinColumn(nullable=false)
     */
    private $vendor;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="redeemed_at", type="datetime", nullable=false)
     */
    private $redeemedAt;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="Entity\User")
     * @ORM\JoinColumn(name="redeemed_by", nullable=true)
     */
    private $redeemedBy;

    /**
     * @var mixed
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $value;

    /**
     * @var Collection|Voucher[]
     *
     * @ORM\OneToMany(targetEntity="Entity\Voucher", cascade={"persist"}, orphanRemoval=false, mappedBy="redemptionBatch")
     */
    private $vouchers;

    public function __construct(Vendor $vendor, User $redeemedBy, array $vouchers, float $value)
    {
        $this->vendor = $vendor;
        $this->redeemedAt = new DateTime();
        $this->redeemedBy = $redeemedBy;
        $this->value = $value;
        $this->vouchers = new ArrayCollection($vouchers);
    }

    /**
     * @param int $id
     *
     * @return VoucherRedemptionBatch
     */
    public function setId(int $id): VoucherRedemptionBatch
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Vendor
     */
    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    /**
     * @param Vendor $vendor
     *
     * @return VoucherRedemptionBatch
     */
    public function setVendor(Vendor $vendor): VoucherRedemptionBatch
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getRedeemedAt(): DateTime
    {
        return $this->redeemedAt;
    }

    /**
     * @param DateTime $redeemedAt
     *
     * @return VoucherRedemptionBatch
     */
    public function setRedeemedAt(DateTime $redeemedAt): VoucherRedemptionBatch
    {
        $this->redeemedAt = $redeemedAt;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getRedeemedBy(): ?User
    {
        return $this->redeemedBy;
    }

    /**
     * @param User $redeemedBy
     *
     * @return VoucherRedemptionBatch
     */
    public function setRedeemedBy(User $redeemedBy): VoucherRedemptionBatch
    {
        $this->redeemedBy = $redeemedBy;

        return $this;
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
     *
     * @return VoucherRedemptionBatch
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return Collection|Voucher[]
     */
    public function getVouchers()
    {
        return $this->vouchers;
    }
}
