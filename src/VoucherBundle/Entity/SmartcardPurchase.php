<?php
declare(strict_types=1);

namespace VoucherBundle\Entity;

use BeneficiaryBundle\Entity\Beneficiary;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Smartcard purchase.
 *
 * @ORM\Table(name="smartcard_purchase")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\SmartcardPurchaseRepository")
 */
class SmartcardPurchase
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $id;

    /**
     * @var Smartcard
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\Smartcard", inversedBy="purchases")
     * @ORM\JoinColumn(nullable=false)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $smartcard;

    /**
     * @var Vendor
     *
     * @ORM\ManyToOne(targetEntity="\VoucherBundle\Entity\Vendor")
     * @ORM\JoinColumn(nullable=false)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $vendor;

    /**
     * @var Collection|SmartcardPurchaseRecord[]
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\SmartcardPurchaseRecord", mappedBy="smartcardPurchase", cascade={"persist"}, orphanRemoval=true)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $records;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="used_at", type="datetime", nullable=true)
     *
     * @SymfonyGroups({"FullSmartcard"})
     */
    private $createdAt;

    /**
     * @var SmartcardRedemptionBatch
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\SmartcardRedemptionBatch", inversedBy="purchases", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $redemptionBatch;

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="text")
     */
    private $hash;

    protected function __construct()
    {
        $this->records = new ArrayCollection();
    }

    public static function create(Smartcard $smartcard, Vendor $vendor, DateTimeInterface $createdAt, ?string $hash = null): SmartcardPurchase
    {
        $entity = new self();
        $entity->vendor = $vendor;
        $entity->createdAt = $createdAt;
        $entity->smartcard = $smartcard;
        $beneficiary = $entity->smartcard->getBeneficiary();
        $entity->hash = $hash ?: self::generateHash($beneficiary, $vendor, $createdAt);
        $smartcard->addPurchase($entity);

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
     * @return Smartcard
     */
    public function getSmartcard(): Smartcard
    {
        return $this->smartcard;
    }

    /**
     * @return Vendor
     */
    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    /**
     * @return Collection|SmartcardPurchaseRecord[]
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * @param Product    $product
     * @param float|null $quantity
     * @param float|null $value
     * @param string|null $currency
     */
    public function addRecord(Product $product, ?float $quantity, ?float $value, ?string $currency): void
    {
        $this->records->add(SmartcardPurchaseRecord::create($this, $product, $quantity, $value, $currency));
    }

    public function getRecordsValue(): float
    {
        $purchased = 0;
        foreach ($this->getRecords() as $record) {
            $purchased += $record->getValue();
        }
        return $purchased;
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @SymfonyGroups({"FullSmartcard"})
     * @return DateTimeInterface|null
     */
    public function getRedeemedAt(): ?DateTimeInterface
    {
        return $this->redemptionBatch ? $this->redemptionBatch->getRedeemedAt() : null;
    }

    /**
     * @return SmartcardRedemptionBatch|null
     */
    public function getRedemptionBatch(): ?SmartcardRedemptionBatch
    {
        return $this->redemptionBatch;
    }

    /**
     * @param SmartcardRedemptionBatch $redemptionBatch
     */
    public function setRedemptionBatch(SmartcardRedemptionBatch $redemptionBatch): void
    {
        $this->redemptionBatch = $redemptionBatch;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->getRecords()->first()->getCurrency();
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    /**
     * @param Beneficiary|null  $beneficiary
     * @param Vendor            $vendor
     * @param DateTimeInterface $createdAt
     *
     * @return string
     */
    public static function generateHash(?Beneficiary $beneficiary, Vendor $vendor, DateTimeInterface $createdAt): string
    {
        $stringToHash = ($beneficiary ? $beneficiary->getId() : null).$vendor->getId().$createdAt->getTimestamp();

        return md5($stringToHash);
    }

}
