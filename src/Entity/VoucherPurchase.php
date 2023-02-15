<?php

namespace Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Voucher purchase.
 */
#[ORM\Table(name: 'voucher_purchase')]
#[ORM\Entity(repositoryClass: 'Repository\VoucherPurchaseRepository')]
class VoucherPurchase
{
    #[SymfonyGroups(['FullVoucher'])]
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id;

    #[SymfonyGroups(['FullVoucher'])]
    #[ORM\ManyToOne(targetEntity: '\Entity\Vendor')]
    #[ORM\JoinColumn(nullable: false)]
    private ?\Entity\Vendor $vendor = null;

    /**
     * @var Collection|Voucher[]
     */
    #[ORM\OneToMany(mappedBy: 'voucherPurchase', targetEntity: 'Entity\Voucher', cascade: ['persist'], orphanRemoval: true)]
    private \Doctrine\Common\Collections\Collection|array $vouchers;

    /**
     * @var Collection|VoucherPurchaseRecord[]
     */
    #[SymfonyGroups(['FullVoucher', 'ValidatedAssistance'])]
    #[ORM\OneToMany(mappedBy: 'voucherPurchase', targetEntity: 'Entity\VoucherPurchaseRecord', cascade: ['persist'], orphanRemoval: true)]
    private \Doctrine\Common\Collections\Collection|array $records;

    /**
     * @var DateTime
     */
    #[SymfonyGroups(['FullVoucher', 'ValidatedAssistance'])]
    #[ORM\Column(name: 'used_at', type: 'datetime', nullable: true)]
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
    public function getVouchers(): \Doctrine\Common\Collections\Collection|array
    {
        return $this->vouchers;
    }

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
    public function getRecords(): \Doctrine\Common\Collections\Collection|array
    {
        return $this->records;
    }

    public function addRecord(Product $product, ?float $quantity, ?float $value): void
    {
        $this->records->add(VoucherPurchaseRecord::create($this, $product, $quantity, $value));
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
