<?php

declare(strict_types=1);

namespace Mapper;

use Entity\HouseholdLocation;
use InvalidArgumentException;
use Serializer\MapperInterface;

class TemporarySettlementAddressMapper implements MapperInterface
{
    /** @var HouseholdLocation */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return
            $object instanceof HouseholdLocation &&
            HouseholdLocation::LOCATION_TYPE_SETTLEMENT === $object->getType() &&
            isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof HouseholdLocation) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . HouseholdLocation::class . ', ' . get_class(
                $object
            ) . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getType(): string
    {
        return $this->object->getType();
    }

    public function getLocationGroup(): string
    {
        return $this->object->getLocationGroup();
    }

    public function getNumber(): ?string
    {
        return $this->object->getAddress()->getNumber();
    }

    public function getStreet(): ?string
    {
        return $this->object->getAddress()->getStreet();
    }

    public function getPostcode(): ?string
    {
        return $this->object->getAddress()->getPostcode();
    }

    public function getLocationId(): int
    {
        return $this->object->getAddress()->getLocation()->getId();
    }

    public function getAdm1Id(): ?int
    {
        return $this->object->getAddress()->getLocation()->getAdm1Id() ?: null;
    }

    public function getAdm2Id(): ?int
    {
        return $this->object->getAddress()->getLocation()->getAdm2Id() ?: null;
    }

    public function getAdm3Id(): ?int
    {
        return $this->object->getAddress()->getLocation()->getAdm3Id() ?: null;
    }

    public function getAdm4Id(): ?int
    {
        return $this->object->getAddress()->getLocation()->getAdm4Id() ?: null;
    }
}
