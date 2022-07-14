<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Entity\Phone;
use NewApiBundle\Serializer\MapperInterface;

class PhoneMapper implements MapperInterface
{
    /** @var Phone */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Phone && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Phone) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Phone::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getNumber(): string
    {
        return $this->object->getNumber();
    }

    public function getType(): ?string
    {
        return $this->object->getType();
    }

    public function getPrefix(): string
    {
        return $this->object->getPrefix();
    }

    public function getProxy(): bool
    {
        return $this->object->getProxy();
    }
}
