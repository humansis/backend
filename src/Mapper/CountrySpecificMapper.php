<?php

declare(strict_types=1);

namespace Mapper;

use Entity\CountrySpecific;
use InvalidArgumentException;
use Serializer\MapperInterface;

class CountrySpecificMapper implements MapperInterface
{
    /** @var CountrySpecific */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof CountrySpecific && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof CountrySpecific) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException('Invalid argument. It should be instance of ' . CountrySpecific::class . ', ' . get_class($object) . ' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getField(): string
    {
        return $this->object->getFieldString();
    }

    public function getType(): string
    {
        return $this->object->getType();
    }

    public function getIso3(): string
    {
        return $this->object->getCountryIso3();
    }
}
