<?php declare(strict_types=1);

namespace NewApiBundle\Mapper\Smartcard;

use NewApiBundle\Entity\SmartcardPurchasedItem;
use NewApiBundle\Serializer\MapperInterface;

class PurchasedItemMapper implements MapperInterface
{
    /** @var SmartcardPurchasedItem */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return
            $object instanceof SmartcardPurchasedItem && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof SmartcardPurchasedItem) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.SmartcardPurchasedItem::class.', '.get_class($object).' given.');
    }

    public function getHouseholdId(): int
    {
        return $this->object->getHousehold()->getId();
    }

    public function getBeneficiaryId(): int
    {
        return $this->object->getBeneficiary()->getId();
    }

    public function getProjectId(): int
    {
        return $this->object->getProject()->getId();
    }

    public function getAssistanceId(): int
    {
        return $this->object->getAssistance()->getId();
    }

    public function getLocationId(): int
    {
        return $this->object->getLocation()->getId();
    }

    public function getAdm1Id(): ?int
    {
        return $this->object->getLocation()->getAdm1Id();
    }

    public function getAdm2Id(): ?int
    {
        return $this->object->getLocation()->getAdm2Id();
    }

    public function getAdm3Id(): ?int
    {
        return $this->object->getLocation()->getAdm3Id();
    }

    public function getAdm4Id(): ?int
    {
        return $this->object->getLocation()->getAdm4Id();
    }

    public function getDatePurchase(): string
    {
        return $this->object->getDatePurchase()->format(\DateTimeInterface::ISO8601);
    }

    public function getSmartcardCode(): string
    {
        return $this->object->getSmartcardCode();
    }

    public function getProductId(): int
    {
        return $this->object->getProduct()->getId();
    }

    public function getUnit(): string
    {
        return (string) $this->object->getProduct()->getUnit();
    }

    public function getValue(): string
    {
        return $this->object->getValue();
    }

    public function getCurrency(): ?string
    {
        return $this->object->getCurrency();
    }

    public function getVendorId(): int
    {
        return $this->object->getVendor()->getId();
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->object->getInvoiceNumber();
    }

    public function getContractNumber(): ?string
    {
        return $this->object->getVendor()->getContractNo();
    }

    public function getIdNumber(): ?string
    {
        return $this->object->getIdNumber();
    }
}
