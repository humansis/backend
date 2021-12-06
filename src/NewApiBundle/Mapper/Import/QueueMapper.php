<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper\Import;

use NewApiBundle\Component\Import\Entity\Queue;
use NewApiBundle\Serializer\MapperInterface;

class QueueMapper implements MapperInterface
{
    /** @var Queue */
    private $object;

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Queue && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof Queue) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Queue::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getValues(): string
    {
        return json_encode($this->object->getContent());
    }

    public function getStatus(): string
    {
        return $this->object->getState();
    }
}
