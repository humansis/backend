<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use CommonBundle\Entity\Adm1;
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
        return $object instanceof Adm1 && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Adm1) {
            $this->object = $object;

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
