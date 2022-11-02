<?php

declare(strict_types=1);

namespace Mapper;

use DateTimeInterface;
use Entity\PurchasedItem;
use InvalidArgumentException;
use Serializer\MapperInterface;

class PurchasedItemMapper implements MapperInterface
{
    private ?\Entity\PurchasedItem $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return
            $object instanceof PurchasedItem && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof PurchasedItem) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . PurchasedItem::class . ', ' . $object::class . ' given.'
        );
    }

    public function getBeneficiaryId(): int
    {
        return $this->object->getBeneficiary()->getId();
    }

    public function getBeneficiaryType(): string
    {
        return $this->object->getBeneficiaryType();
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
        return $this->object->getDatePurchase()->format(DateTimeInterface::ISO8601);
    }

    public function getCommodityId(): int
    {
        return $this->object->getCommodity()->getId();
    }

    public function getModalityType(): string
    {
        return $this->object->getModalityType();
    }

    public function getCarrierNumber(): string
    {
        return $this->object->getCarrierNumber();
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
        return (string) $this->object->getValue();
    }

    public function getCurrency(): string
    {
        return $this->object->getCurrency();
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->object->getInvoiceNumber();
    }

    public function getVendorId(): int
    {
        return $this->object->getVendor()->getId();
    }

    public function getContractNumber(): ?string
    {
        return $this->object->getVendor()->getContractNo();
    }
}
