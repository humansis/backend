<?php

namespace Mapper;

use Entity\Location;
use Serializer\MapperInterface;

class LocationMapper implements MapperInterface
{
    /** @var Location */
    private $object;
    
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Location  && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof Location) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException(
            'Invalid argument. It should be instance of '.Location::class.', '.get_class($object).' given.'
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

    public function getCountryIso3(): string
    {
        return $this->object->getCountryIso3();
    }

    public function getLocationId(): int
    {
        return $this->object->getId();
    }
}