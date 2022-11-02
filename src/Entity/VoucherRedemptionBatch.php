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
     * @var Collection|Voucher[]
     *
     * @ORM\OneToMany(targetEntity="Entity\Voucher", cascade={"persist"}, orphanRemoval=false, mappedBy="redemptionBatch")
     */
    private \Doctrine\Common\Collections\Collection|array $vouchers;

    public function __construct(/**
         *
         * @ORM\ManyToOne(targetEntity="\Entity\Vendor")
         * @ORM\JoinColumn(nullable=false)
         */
        private Vendor $vendor, /**
         *
         * @ORM\ManyToOne(targetEntity="Entity\User")
         * @ORM\JoinColumn(name="redeemed_by", nullable=true)
         */
        private ?\Entity\User $redeemedBy,
        array $vouchers, /**
         * @ORM\Column(name="value", type="decimal", precision=10, scale=2, nullable=true)
         */
        private float $value
    ) {
        $this->redeemedAt = new DateTime();
        $this->vouchers = new ArrayCollection($vouchers);
    }

    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    public function setVendor(Vendor $vendor): VoucherRedemptionBatch
    {
        $this->vendor = $vendor;

        return $this;
    }

    public function getRedeemedAt(): DateTime
    {
        return $this->redeemedAt;
    }

    public function setRedeemedAt(DateTime $redeemedAt): VoucherRedemptionBatch
    {
        $this->redeemedAt = $redeemedAt;

        return $this;
    }

    public function getRedeemedBy(): ?User
    {
        return $this->redeemedBy;
    }

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
     * @return VoucherRedemptionBatch
     */
    public function setValue(mixed $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return Collection|Voucher[]
     */
    public function getVouchers(): \Doctrine\Common\Collections\Collection|array
    {
        return $this->vouchers;
    }
}
