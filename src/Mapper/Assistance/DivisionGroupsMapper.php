<?php

declare(strict_types=1);

namespace Mapper\Assistance;

use Entity\DivisionGroup;
use InvalidArgumentException;
use Serializer\MapperInterface;

class DivisionGroupsMapper implements MapperInterface
{
    /** @var DivisionGroup */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof DivisionGroup && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof DivisionGroup) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException('Invalid argument. It should be instance of ' . DivisionGroup::class . ', ' . get_class($object) . ' given.');
    }

    public function getRangeFrom(): int
    {
        return $this->object->getRangeFrom();
    }

    public function getRangeTo(): ?int
    {
        return $this->object->getRangeTo();
    }

    public function getValue(): string
    {
        return $this->object->getValue();
    }
}
