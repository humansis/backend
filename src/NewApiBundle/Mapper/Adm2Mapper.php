<?php

namespace NewApiBundle\Mapper;

use CommonBundle\Entity\Adm2;
use NewApiBundle\Serializer\MapperInterface;

class Adm2Mapper implements MapperInterface
{
    /** @var Adm2 */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Adm2 && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Adm2) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Adm2::class.', '.get_class($object).' given.');
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

    public function getAdm1Id(): int
    {
        return $this->object->getAdm1()->getId();
    }

    public function getLocationId(): int
    {
        return $this->object->getLocation()->getId();
    }
}
