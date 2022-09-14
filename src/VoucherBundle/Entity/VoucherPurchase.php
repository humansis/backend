<?php

namespace VoucherBundle\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;


/**
 * Voucher purchase.
 *
 * @ORM\Table(name="voucher_purchase")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\VoucherPurchaseRepository")
 */
class VoucherPurchase extends AbstractEntity
{

    /**
     * @var Vendor
     *
     * @ORM\ManyToOne(targetEntity="\VoucherBundle\Entity\Vendor")
     * @ORM\JoinColumn(nullable=false)
     *
     * @SymfonyGroups({"FullVoucher"})
     */
    private $vendor;

    /**
     * @var Collection|Voucher[]
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\Voucher", mappedBy="voucherPurchase", cascade={"persist"}, orphanRemoval=true)
     */
    private $vouchers;

    /**
     * @var Collection|VoucherPurchaseRecord[]
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\VoucherPurchaseRecord", mappedBy="voucherPurchase", cascade={"persist"}, orphanRemoval=true)
     *
     * @SymfonyGroups({"FullVoucher", "ValidatedAssistance"})
     */
    private $records;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="used_at", type="datetime", nullable=true)
     *
     * @SymfonyGroups({"FullVoucher", "ValidatedAssistance"})
     */
    private $createdAt;

    protected function __construct()
    {
        $this->vouchers = new ArrayCollection();
        $this->records = new ArrayCollection();
    }

    public static function create(Vendor $vendor, DateTimeInterface $createdAt)
    {
        $entity = new self();
        $entity->vendor = $vendor;
        $entity->createdAt = $createdAt;

        return $entity;
    }

    /**
     * @return mixed
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @return Collection|Voucher[]
     */
    public function getVouchers()
    {
        return $this->vouchers;
    }

    /**
     * @param Voucher $voucher
     */
    public function addVoucher(Voucher $voucher): void
    {
        if (!$this->vouchers->contains($voucher)) {
            $voucher->setVoucherPurchase($this);
            $this->vouchers->add($voucher);
        }
    }

    /**
     * @return Collection|VoucherPurchaseRecord[]
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * @param Product    $product
     * @param float|null $quantity
     * @param float|null $value
     */
    public function addRecord(Product $product, ?float $quantity, ?float $value): void
    {
        $this->records->add(VoucherPurchaseRecord::create($this, $product, $quantity, $value));
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
