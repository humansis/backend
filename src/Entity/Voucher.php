<?php

namespace Entity;

use Utils\ExportableInterface;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Voucher.
 *
 * @ORM\Table(name="voucher")
 * @ORM\Entity(repositoryClass="Repository\VoucherRepository")
 */
class Voucher implements ExportableInterface
{
    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    #[SymfonyGroups(['FullVoucher'])]
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\VoucherPurchase", inversedBy="vouchers")
     * @ORM\JoinColumn(nullable=true)
     */
    #[SymfonyGroups(['FullVoucher'])]
    private $voucherPurchase;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Entity\VoucherRedemptionBatch", inversedBy="vouchers", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private ?\Entity\VoucherRedemptionBatch $redemptionBatch = null;

    public function __construct(
        /**
         * @ORM\Column(name="code", type="string", length=255, unique=true)
         */
        #[SymfonyGroups(['FullVoucher'])]
        private string $code,
        /**
         * @ORM\Column(name="value", type="integer")
         */
        #[SymfonyGroups(['FullVoucher', 'FullBooklet', 'ValidatedAssistance'])]
        private int $value,
        /**
         * @ORM\ManyToOne(targetEntity="\Entity\Booklet", inversedBy="vouchers")
         * @ORM\JoinColumn(nullable=false)
         */
        #[SymfonyGroups(['FullVoucher'])]
        private Booklet $booklet
    ) {
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

    /**
     * Returns an array representation of this class in order to prepare the export.
     */
    public function getMappedValueForExport(): array
    {
        return [
            'Booklet Number' => $this->getBooklet()->getCode(),
            'Voucher Codes' => $this->getCode(),
        ];
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
