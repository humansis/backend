<?php
declare(strict_types=1);

namespace Mapper;

use Entity\NationalId;
use Serializer\MapperInterface;

class NationalIdMapper implements MapperInterface
{
    /** @var NationalId */
    private $object;

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

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.NationalId::class.', '.get_class($object).' given.');
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
}
