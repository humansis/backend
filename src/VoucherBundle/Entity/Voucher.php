<?php

namespace VoucherBundle\Entity;

use DateTime;
use DistributionBundle\Entity\DistributionBeneficiary;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Type as JMS_Type;
use CommonBundle\Utils\ExportableInterface;
use UserBundle\Entity\User;

/**
 * Voucher
 *
 * @ORM\Table(name="voucher")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\VoucherRepository")
 */
class Voucher implements ExportableInterface
{
    const STATE_UNASSIGNED = 'unassigned';
    const STATE_DISTRIBUTED = 'distributed';
    const STATE_USED = 'used';
    const STATE_REDEEMED = 'redeemed';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullVoucher"})
     */
    private $id;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="used_at", type="datetime", nullable=true)
     * @JMS_Type("DateTime<'d-m-Y'>")
     * @Groups({"FullVoucher", "ValidatedDistribution"})
     */
    private $usedAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="redeemed_at", type="datetime", nullable=true)
     * @JMS_Type("DateTime<'d-m-Y'>")
     * @Groups({"FullVoucher", "ValidatedDistribution"})
     */
    private $redeemedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     * @Groups({"FullVoucher"})
     */
    private $code;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     * @Groups({"FullBooklet", "ValidatedDistribution"})
     */
    private $status = self::STATE_UNASSIGNED;

    /**
     * @var int
     *
     * @ORM\Column(name="value", type="integer")
     * @Groups({"FullVoucher", "FullBooklet", "ValidatedDistribution"})
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity="\VoucherBundle\Entity\Booklet", inversedBy="vouchers")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"FullVoucher"})
     */
    private $booklet;

    /**
     * @ORM\ManyToOne(targetEntity="\VoucherBundle\Entity\Vendor", inversedBy="vouchers")
     * @ORM\JoinColumn(nullable=true)
     * @Groups({"FullVoucher"})
     */
    private $vendor;

    /**
     * @var Collection|VoucherRecord[]
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\VoucherRecord", mappedBy="voucher", cascade={"persist"}, orphanRemoval=true)
     * @Groups({"FullVoucher", "ValidatedDistribution"})
     */
    private $records;

    public function __construct(string $code, int $value, Booklet $booklet)
    {
        $this->code = $code;
        $this->value = $value;
        $this->booklet = $booklet;
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
     * Get usedAt.
     *
     * @return DateTime
     */
    public function getUsedAt()
    {
        return $this->usedAt;
    }

    /**
     * @return DateTime
     */
    public function getRedeemedAt(): DateTime
    {
        return $this->redeemedAt;
    }

    public function distribute() : void
    {
        $this->status = self::STATE_DISTRIBUTED;
    }

    public function use(DateTime $when) : void
    {
        $this->status = self::STATE_USED;
        $this->usedAt = $when;
    }

    public function redeem(DateTime $when) : void
    {
        $this->status = self::STATE_REDEEMED;
        $this->redeemedAt = $when;
    }

    /**
     * Set value.
     *
     * @param integer $value
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
     * @return integer
     */
    public function getValue()
    {
        return $this->value;
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

    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    public function setVendor(Vendor $vendor = null): self
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * @return Collection|VoucherRecord[]
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * @param VoucherRecord $voucherRecord
     * @return $this
     */
    public function addRecord(VoucherRecord $voucherRecord): self
    {
        foreach ($this->records as $record) {
            if ($record->getProduct()->getId() === $voucherRecord->getProduct()->getId()) {
                $record->setQuantity($record->getQuantity() + $voucherRecord->getQuantity() ?: null);
                $record->setValue($record->getValue() + $voucherRecord->getValue() ?: null);
                return $this;
            }
        }

        $voucherRecord->setVoucher($this);
        $this->records->add($voucherRecord);

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

     /**
     * Returns an array representation of this class in order to prepare the export
     * @return array
     */
    public function getMappedValueForExport(): array
    {
        return [
            'Booklet Number' => $this->getBooklet()->getCode(),
            'Voucher Codes' => $this->getCode(),
        ];
    }
}
