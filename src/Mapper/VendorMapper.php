<?php

namespace Mapper;

use InvalidArgumentException;
use Serializer\MapperInterface;
use Entity\Vendor;

class VendorMapper implements MapperInterface
{
    private ?\Entity\Vendor $object = null;

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Vendor && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof Vendor) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Vendor::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getShop(): ?string
    {
        return $this->object->getShop();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getAddressStreet(): ?string
    {
        return $this->object->getAddressStreet();
    }

    public function getAddressNumber(): ?string
    {
        return $this->object->getAddressNumber();
    }

    public function getAddressPostcode(): ?string
    {
        return $this->object->getAddressPostcode();
    }

    public function getLocationId(): int
    {
        return $this->object->getLocation()->getId();
    }

    public function getAdm1Id(): ?int
    {
        return $this->object->getLocation()->getAdm1Id() ?: null;
    }

    public function getAdm2Id(): ?int
    {
        return $this->object->getLocation()->getAdm2Id() ?: null;
    }

    public function getAdm3Id(): ?int
    {
        return $this->object->getLocation()->getAdm3Id() ?: null;
    }

    public function getAdm4Id(): ?int
    {
        return $this->object->getLocation()->getAdm4Id() ?: null;
    }

    public function getUserId(): int
    {
        return $this->object->getUser()->getId();
    }

    public function getVendorNo(): ?string
    {
        return $this->object->getVendorNo();
    }

    public function getContractNo(): ?string
    {
        return $this->object->getContractNo();
    }

    public function getCanSellFood(): bool
    {
        return $this->object->canSellFood();
    }

    public function getCanSellNonFood(): bool
    {
        return $this->object->canSellNonFood();
    }

    public function getCanSellCashback(): bool
    {
        return $this->object->canSellCashback();
    }

    public function getCanDoRemoteDistributions(): bool
    {
        return $this->object->canDoRemoteDistributions();
    }
}
