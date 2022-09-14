<?php

namespace VoucherBundle\Entity;

use CommonBundle\Utils\ExportableInterface;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Voucher.
 *
 * @ORM\Table(name="voucher")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\VoucherRepository")
 */
class Voucher extends AbstractEntity implements ExportableInterface
{
    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     * @SymfonyGroups({"FullVoucher"})
     */
    private $code;

    /**
     * @var int
     *
     * @ORM\Column(name="value", type="integer")
     * @SymfonyGroups({"FullVoucher", "FullBooklet", "ValidatedAssistance"})
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity="\VoucherBundle\Entity\Booklet", inversedBy="vouchers")
     * @ORM\JoinColumn(nullable=false)
     * @SymfonyGroups({"FullVoucher"})
     */
    private $booklet;

    /**
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\VoucherPurchase", inversedBy="vouchers")
     * @ORM\JoinColumn(nullable=true)
     * @SymfonyGroups({"FullVoucher"})
     */
    private $voucherPurchase;

    /**
     * @var VoucherRedemptionBatch|null
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\VoucherRedemptionBatch", inversedBy="vouchers", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $redemptionBatch;

    public function __construct(string $code, int $value, Booklet $booklet)
    {
        $this->code = $code;
        $this->value = $value;
        $this->booklet = $booklet;
    }


    /**
     * Set value.
     *
     * @param int $value
     *
     * @return Voucher
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get individual value.
     *
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return DateTimeInterface|null
     * @SymfonyGroups({"FullVoucher", "ValidatedAssistance"})
     */
    public function getRedeemedAt(): ?DateTimeInterface
    {
        if (null !== $this->redemptionBatch) {
            return $this->getRedemptionBatch()->getRedeemedAt();
        }

        return null;
    }

    /**
     * @SymfonyGroups({"FullVoucher", "FullBooklet", "ValidatedAssistance"})
     *
     * @return string|null
     */
    public function getUsedAt(): ?string
    {
        if (!$this->getUsedAtDate()) {
            return null;
        }
        return $this->getUsedAtDate()->format('Y-m-d');
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getUsedAtDate(): ?DateTimeInterface
    {
        if (!$this->getVoucherPurchase()) {
            return null;
        }
        return $this->getVoucherPurchase()->getCreatedAt();
    }

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return Voucher
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
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

    /**
     * @return VoucherPurchase|null
     */
    public function getVoucherPurchase(): ?VoucherPurchase
    {
        return $this->voucherPurchase;
    }

    /**
     * @param VoucherPurchase $purchase
     *
     * @return $this
     */
    public function setVoucherPurchase(VoucherPurchase $purchase): self
    {
        $this->voucherPurchase = $purchase;

        return $this;
    }

    /**
     * Returns an array representation of this class in order to prepare the export.
     *
     * @return array
     */
    public function getMappedValueForExport(): array
    {
        return [
            'Booklet Number' => $this->getBooklet()->getCode(),
            'Voucher Codes' => $this->getCode(),
        ];
    }

    /**
     * @return VoucherRedemptionBatch|null
     */
    public function getRedemptionBatch(): ?VoucherRedemptionBatch
    {
        return $this->redemptionBatch;
    }

    /**
     * @param VoucherRedemptionBatch|null $redemptionBatch
     *
     * @return Voucher
     */
    public function setRedemptionBatch(?VoucherRedemptionBatch $redemptionBatch): Voucher
    {
        $this->redemptionBatch = $redemptionBatch;

        return $this;
    }
}
