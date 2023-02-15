<?php

namespace Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Voucher.
 */
#[ORM\Table(name: 'voucher')]
#[ORM\Entity(repositoryClass: 'Repository\VoucherRepository')]
class Voucher
{
    #[SymfonyGroups(['FullVoucher'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id;

    #[SymfonyGroups(['FullVoucher'])]
    #[ORM\ManyToOne(targetEntity: 'Entity\VoucherPurchase', inversedBy: 'vouchers')]
    #[ORM\JoinColumn(nullable: true)]
    private ?VoucherPurchase $voucherPurchase;

    #[ORM\ManyToOne(targetEntity: 'Entity\VoucherRedemptionBatch', cascade: ['persist'], inversedBy: 'vouchers')]
    #[ORM\JoinColumn(nullable: true)]
    private ?\Entity\VoucherRedemptionBatch $redemptionBatch = null;

    #[SymfonyGroups(['FullVoucher'])]
    #[ORM\Column(name: 'code', type: 'string', length: 255, unique: true)]
    private string $code;

    #[SymfonyGroups(['FullVoucher', 'FullBooklet', 'ValidatedAssistance'])]
    #[ORM\Column(name: 'value', type: 'integer')]
    private int $value;

    #[SymfonyGroups(['FullVoucher'])]
    #[ORM\ManyToOne(targetEntity: '\Entity\Booklet', inversedBy: 'vouchers')]
    #[ORM\JoinColumn(nullable: false)]
    private Booklet $booklet;

    public function __construct(string $code, int $value, Booklet $booklet)
    {
        $this->code = $code;
        $this->value = $value;
        $this->booklet = $booklet;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    #[SymfonyGroups(['FullVoucher', 'ValidatedAssistance'])]
    public function getRedeemedAt(): ?DateTimeInterface
    {
        if (null !== $this->redemptionBatch) {
            return $this->getRedemptionBatch()->getRedeemedAt();
        }

        return null;
    }

    #[SymfonyGroups(['FullVoucher', 'FullBooklet', 'ValidatedAssistance'])]
    public function getUsedAt(): ?string
    {
        if (!$this->getUsedAtDate()) {
            return null;
        }

        return $this->getUsedAtDate()->format('Y-m-d');
    }

    public function getUsedAtDate(): ?DateTimeInterface
    {
        return $this->getVoucherPurchase()?->getCreatedAt();
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getBooklet(): Booklet
    {
        return $this->booklet;
    }

    public function setBooklet(Booklet $booklet): self
    {
        $this->booklet = $booklet;

        return $this;
    }

    public function getVoucherPurchase(): ?VoucherPurchase
    {
        return $this->voucherPurchase;
    }

    /**
     * @return $this
     */
    public function setVoucherPurchase(VoucherPurchase $purchase): self
    {
        $this->voucherPurchase = $purchase;

        return $this;
    }


    public function getRedemptionBatch(): ?VoucherRedemptionBatch
    {
        return $this->redemptionBatch;
    }

    public function setRedemptionBatch(?VoucherRedemptionBatch $redemptionBatch): Voucher
    {
        $this->redemptionBatch = $redemptionBatch;

        return $this;
    }
}
