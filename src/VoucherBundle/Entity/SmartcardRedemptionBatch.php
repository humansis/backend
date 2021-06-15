<?php

declare(strict_types=1);

namespace VoucherBundle\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use ProjectBundle\Entity\Project;
use UserBundle\Entity\User;

/**
 * Smartcard purchase batch for redemption feature.
 *
 * @ORM\Table(name="smartcard_redemption_batch")
 * @ORM\Entity(repositoryClass="\VoucherBundle\Repository\SmartcardRedemptionBatchRepository")
 */
class SmartcardRedemptionBatch implements JsonSerializable
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Vendor
     *
     * @ORM\ManyToOne(targetEntity="\VoucherBundle\Entity\Vendor")
     * @ORM\JoinColumn(nullable=false)
     */
    private $vendor;

    /**
     * @var Project|null
     *
     * @ORM\ManyToOne(targetEntity="\ProjectBundle\Entity\Project")
     * @ORM\JoinColumn(nullable=true)
     */
    private $project;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="redeemed_at", type="datetime", nullable=false)
     */
    private $redeemedAt;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     * @ORM\JoinColumn(name="redeemed_by", nullable=false)
     */
    private $redeemedBy;

    /**
     * @var mixed
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", nullable=true)
     */
    private $currency;

    /**
     * @var string|null
     *
     * @ORM\Column(name="contract_no", type="string", nullable=true)
     */
    private $contractNo;

    /**
     * @var string|null
     *
     * @ORM\Column(name="vendor_no", type="string", nullable=true)
     */
    private $vendorNo;

    /**
     * @var Collection|SmartcardPurchase[]
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\SmartcardPurchase", mappedBy="redemptionBatch", cascade={"persist"}, orphanRemoval=false)
     */
    private $purchases;

    /**
     * SmartcardPurchaseBatch constructor.
     *
     * @param Vendor       $vendor
     * @param Project|null $project
     * @param DateTime     $redeemedAt
     * @param User         $redeemedBy
     * @param mixed        $value
     * @param string       $currency
     * @param string|null  $contractNo
     * @param string|null  $vendorNo
     * @param iterable     $purchases
     */
    public function __construct(
        Vendor $vendor,
        ?Project $project,
        DateTime $redeemedAt,
        User $redeemedBy,
        $value,
        string $currency,
        ?string $contractNo,
        ?string $vendorNo,
        iterable $purchases
    )
    {
        $this->vendor = $vendor;
        $this->project = $project;
        $this->redeemedAt = $redeemedAt;
        $this->redeemedBy = $redeemedBy;
        $this->value = $value;
        $this->currency = $currency;
        $this->purchases = new ArrayCollection($purchases);
        $this->contractNo = $contractNo;
        $this->vendorNo = $vendorNo;
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
     * @return Vendor
     */
    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    /**
     * @return Project|null
     */
    public function getProject(): ?Project
    {
        return $this->project;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getRedeemedAt(): ?DateTimeInterface
    {
        return $this->redeemedAt;
    }

    /**
     * @param DateTimeInterface|null $redeemedAt
     */
    public function setRedeemedAt(?DateTimeInterface $redeemedAt): void
    {
        $this->redeemedAt = $redeemedAt;
    }

    /**
     * @return User
     */
    public function getRedeemedBy(): User
    {
        return $this->redeemedBy;
    }

    /**
     * @param User $redeemedBy
     */
    public function setRedeemedBy(User $redeemedBy): void
    {
        $this->redeemedBy = $redeemedBy;
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
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return Collection|SmartcardPurchase[]
     */
    public function getPurchases(): Collection
    {
        return $this->purchases;
    }

    /**
     * @param Collection|SmartcardPurchase[] $purchases
     */
    public function setPurchases($purchases): void
    {
        $this->purchases = $purchases;
    }

    /**
     * @return string|null
     */
    public function getContractNo(): ?string
    {
        return $this->contractNo;
    }

    /**
     * @return string|null
     */
    public function getVendorNo(): ?string
    {
        return $this->vendorNo;
    }

    /**
     * @return string|null
     */
    public function getInvoiceNo(): ?string
    {
        return $this->getId() ? sprintf('%06d', $this->getId()) : null;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'datetime' => $this->redeemedAt->format('U'),
            'date' => $this->redeemedAt->format('d-m-Y H:i'),
            'count' => $this->purchases->count(),
            'value' => (float) $this->value,
            'currency' => $this->currency,
            'contract_no' => $this->contractNo,
            'vendor_no' => $this->vendorNo,
            'invoice_number' => $this->getInvoiceNo(),
            'project_id' => $this->getProject() ? $this->getProject()->getId() : null,
            'project_name' => $this->getProject() ? $this->getProject()->getName() : null,
        ];
    }
}
