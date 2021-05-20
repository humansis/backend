<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Component\Import\ValueObject\QueueProgressValueObject;
use NewApiBundle\Serializer\MapperInterface;

class QueueProgressMapper implements MapperInterface
{
    /** @var QueueProgressValueObject */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof QueueProgressValueObject;
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof QueueProgressValueObject) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.QueueProgressValueObject::class.', '.get_class($object).' given.');
    }

    public function getTotalCount(): int
    {
        return $this->object->getTotalCount();
    }

    public function getCorrect(): int
    {
        return $this->object->getCorrect();
    }

    public function getFailed(): int
    {
        return $this->object->getFailed();
    }
}
