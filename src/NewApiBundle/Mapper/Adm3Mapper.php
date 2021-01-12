<?php

declare(strict_types=1);

namespace NewApiBundle\Mapper;

use CommonBundle\Entity\Adm3;
use NewApiBundle\Serializer\MapperInterface;

class Adm3Mapper implements MapperInterface
{
    /** @var Adm3 */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Adm3 && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Adm3) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Adm3::class.', '.get_class($object).' given.');
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

    public function getAdm2Id(): int
    {
        return $this->object->getAdm2()->getId();
    }

    public function getLocationId(): int
    {
        return $this->object->getLocation()->getId();
    }
}
