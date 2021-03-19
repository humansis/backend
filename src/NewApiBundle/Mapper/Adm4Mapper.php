<?php

declare(strict_types=1);

namespace NewApiBundle\Mapper;

use CommonBundle\Entity\Adm4;
use CommonBundle\Entity\Location;
use NewApiBundle\Serializer\MapperInterface;

class Adm4Mapper implements MapperInterface
{
    /** @var Adm4 */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        if (!isset($context[self::NEW_API]) || false === $context[self::NEW_API]) {
            return false;
        }

        return $object instanceof Adm4 || ($object instanceof Location && null !== $object->getAdm4());
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Adm4) {
            $this->object = $object;

            return;
        } elseif ($object instanceof Location && null !== $object->getAdm4()) {
            $this->object = $object->getAdm4();

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Adm4::class.', '.get_class($object).' given.');
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

    public function getAdm3Id(): int
    {
        return $this->object->getAdm3()->getId();
    }

    public function getLocationId(): int
    {
        return $this->object->getLocation()->getId();
    }
}
