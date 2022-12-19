<?php

namespace Mapper;

use Entity\Location;
use InvalidArgumentException;
use Serializer\MapperInterface;

class LocationMapper implements MapperInterface
{
    private ?\Entity\Location $object = null;

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Location && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof Location) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Location::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getCode(): string
    {
        return $this->object->getCode();
    }

    /**
     * @deprecated use $this->getId()
     * backward compatibility for FE
     */
    public function getLocationId(): int
    {
        return $this->object->getId();
    }

    public function getParentId(): ?int
    {
        return $this->object->getParentLocation()
            ? $this->object->getParentLocation()->getId()
            : null;
    }

    public function getHasDuplicity(): bool
    {
        return $this->object->getDuplicityCount() > 0;
    }

    public function toFlatArray(?Location $location): ?array
    {
        if (!$location) {
            return null;
        }

        return $this->expandLocation($location);
    }

    private function expandLocation(Location $location): array
    {
        $ids = [
            'adm1' => null,
            'adm2' => null,
            'adm3' => null,
            'adm4' => null,
        ];

        while ($location !== null) {
            $ids['adm' . $location->getLvl()] = $location->getId();
            $location = $location->getParent();
        }

        return $ids;
    }

    public function toName(Location $location): string
    {
        $names = [];

        while ($location !== null) {
            $names[] = $location->getName();
            $location = $location->getParent();
        }

        return implode(', ', $names);
    }
}
