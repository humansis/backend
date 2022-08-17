<?php
declare(strict_types=1);

namespace Mapper;

use Entity\Address;
use Serializer\MapperInterface;

class AddressMapper implements MapperInterface
{
    /** @var Address */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Address && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Address) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Address::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getNumber(): ?string
    {
        return $this->object->getNumber();
    }

    public function getStreet(): ?string
    {
        return $this->object->getStreet();
    }

    public function getPostcode(): ?string
    {
        return $this->object->getPostcode();
    }

    public function getLocationId(): int
    {
        return $this->object->getLocation()->getId();
    }

    public function getAdm1Id(): ?int
    {
        return $this->object->getLocation()->getAdm1Id() ?: null;
    }

    public function getAdm2Id(): ?int
    {
        return $this->object->getLocation()->getAdm2Id() ?: null;
    }

    public function getAdm3Id(): ?int
    {
        return $this->object->getLocation()->getAdm3Id() ?: null;
    }

    public function getAdm4Id(): ?int
    {
        return $this->object->getLocation()->getAdm4Id() ?: null;
    }
}
