<?php

namespace Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class VoucherRedemptionBatch.
 *
 * @ORM\Table(name="voucher_redemption_batch")
 * @ORM\Entity(repositoryClass="\Repository\VoucherRedemptionBatchRepository")
 */
class VoucherRedemptionBatch
{
    /**
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="redeemed_at", type="datetime", nullable=false)
     */
    private DateTime $redeemedAt;

    /**
     * @var Collection|Voucher[]
     *
     * @ORM\OneToMany(targetEntity="Entity\Voucher", cascade={"persist"}, orphanRemoval=false, mappedBy="redemptionBatch")
     */
    private Collection | array $vouchers;

    /**
     * @ORM\ManyToOne(targetEntity="\Entity\Vendor")
     * @ORM\JoinColumn(nullable=false)
     */
    private Vendor $vendor;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\User")
     * @ORM\JoinColumn(name="redeemed_by", nullable=true)
     */
    private ?\Entity\User $redeemedBy;

    /**
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2, nullable=true)
     */
    private float $value;

    public function __construct(Vendor $vendor, User $redeemedBy, array $vouchers, float $value)
    {
        $this->vendor = $vendor;
        $this->redeemedAt = new DateTime();
        $this->redeemedBy = $redeemedBy;
        $this->value = $value;
        $this->vouchers = new ArrayCollection($vouchers);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): VoucherRedemptionBatch
    {
        $this->id = $id;

        return $this;
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

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return Collection|Voucher[]
     */
    public function getVouchers(): Collection | array
    {
        return $this->vouchers;
    }
}
