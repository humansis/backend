<?php

declare(strict_types=1);

namespace Mapper;

use Component\Country\Country;
use InvalidArgumentException;
use Serializer\MapperInterface;

class CountryMapper implements MapperInterface
{
    /** @var Country */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Country && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Country) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException('Invalid argument. It should be instance of ' . Country::class . ', ' . get_class($object) . ' given.');
    }

    public function getIso3(): string
    {
        return $this->object->getIso3();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getCurrency(): string
    {
        return $this->object->getCurrency();
    }
}
