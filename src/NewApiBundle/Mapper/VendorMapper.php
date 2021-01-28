<?php

namespace NewApiBundle\Mapper;

use InvalidArgumentException;
use NewApiBundle\Serializer\MapperInterface;
use VoucherBundle\Entity\Vendor;

class VendorMapper implements MapperInterface
{
    /** @var Vendor */
    private $object;

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

        throw new InvalidArgumentException('Invalid argument. It should be instance of '.Vendor::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getShop(): string
    {
        return $this->object->getShop();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getUsername(): string
    {
        return $this->object->getUser()->getUsername();
    }

    public function getSalt(): ?string
    {
        return $this->object->getUser()->getSalt();
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

    public function getLocationId(): ?int
    {
        if (null === $this->object->getLocation()) {
            return null;
        }

        return $this->object->getLocation()->getId();
    }
}
