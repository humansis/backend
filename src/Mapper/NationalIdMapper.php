<?php

declare(strict_types=1);

namespace Mapper;

use Entity\NationalId;
use InvalidArgumentException;
use Serializer\MapperInterface;

class NationalIdMapper implements MapperInterface
{
    private ?\Entity\NationalId $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof NationalId && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof NationalId) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . NationalId::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getNumber(): string
    {
        return $this->object->getIdNumber();
    }

    public function getType(): string
    {
        return $this->object->getIdType();
    }

    public function getPriority(): int
    {
        return $this->object->getPriority();
    }
}
