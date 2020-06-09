<?php

namespace VoucherBundle\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Type as JMS_Type;

/**
 * Smartcard purchase.
 *
 * @ORM\Table(name="smartcard_purchase")
 * @ORM\Entity()
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
     * @Groups({"FullSmartcard"})
     */
    private $id;

    /**
     * @var Smartcard
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\Smartcard")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Groups({"FullSmartcard"})
     */
    private $smartcard;

    /**
     * @var Vendor
     *
     * @ORM\ManyToOne(targetEntity="\VoucherBundle\Entity\Vendor")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Groups({"FullSmartcard"})
     */
    private $vendor;

    /**
     * @var Collection|SmartcardPurchaseRecord[]
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\SmartcardPurchaseRecord", mappedBy="voucherPurchase", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"FullSmartcard"})
     */
    private $records;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="used_at", type="datetime", nullable=true)
     *
     * @JMS_Type("DateTime<'d-m-Y'>")
     * @Groups({"FullSmartcard"})
     */
    private $createdAt;

    protected function __construct()
    {
        $this->records = new ArrayCollection();
    }

    public static function create(Smartcard $smartcard, Vendor $vendor, DateTimeInterface $createdAt)
    {
        $entity = new self();
        $entity->vendor = $vendor;
        $entity->createdAt = $createdAt;
        $entity->smartcard = $smartcard;

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
     * @param Product $product
     * @param float|null $quantity
     * @param float|null $value
     */
    public function addRecord(Product $product, ?float $quantity, ?float $value): void
    {
        $this->records->add(SmartcardPurchaseRecord::create($this, $product, $quantity, $value));
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
