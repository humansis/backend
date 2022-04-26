<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Serializer\MapperInterface;

class ImportQueueMapper implements MapperInterface
{
    /** @var ImportQueue */
    private $object;

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof ImportQueue && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof ImportQueue) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.ImportQueue::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getValues(): array
    {
        $extractValue = function ($values) {
            return $values['value'];
        };
        $extractValueFromAllBeneficiaries = function ($values) use ($extractValue) {
            return array_map($extractValue, $values);
        };
        return array_map($extractValueFromAllBeneficiaries, $this->object->getContent());
    }

    public function getStatus(): string
    {
        return $this->object->getState();
    }
}
