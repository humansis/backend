<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Location;
use NewApiBundle\Serializer\MapperInterface;

class Adm1Mapper implements MapperInterface
{
    /** @var Adm1 */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        if (!isset($context[self::NEW_API]) || false === $context[self::NEW_API]) {
            return false;
        }

        return $object instanceof Adm1 || ($object instanceof Location && null !== $object->getAdm1());
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Adm1) {
            $this->object = $object;

            return;
        } elseif ($object instanceof Location && null !== $object->getAdm1()) {
            $this->object = $object->getAdm1();

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Adm1::class.', '.get_class($object).' given.');
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
        return $this->object->getCountryISO3();
    }

    public function getLocationId(): int
    {
        return $this->object->getLocation()->getId();
    }
}
