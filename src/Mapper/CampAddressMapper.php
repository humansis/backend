<?php

declare(strict_types=1);

namespace Mapper;

use Entity\HouseholdLocation;
use InvalidArgumentException;
use Serializer\MapperInterface;

class CampAddressMapper implements MapperInterface
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
            HouseholdLocation::LOCATION_TYPE_CAMP === $object->getType() &&
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

        throw new InvalidArgumentException('Invalid argument. It should be instance of ' . HouseholdLocation::class . ', ' . get_class($object) . ' given.');
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

    public function getCampId(): int
    {
        return $this->object->getCampAddress()->getCamp()->getId();
    }

    public function getTentNumber(): string
    {
        return $this->object->getCampAddress()->getTentNumber();
    }
}
