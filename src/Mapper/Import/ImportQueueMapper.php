<?php

declare(strict_types=1);

namespace Mapper\Import;

use Entity\ImportQueue;
use InvalidArgumentException;
use Serializer\MapperInterface;

class ImportQueueMapper implements MapperInterface
{
    private ?\Entity\ImportQueue $object = null;

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

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . ImportQueue::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getValues(): array
    {
        $extractValue = fn($values) => $values['value'];
        $extractValueFromAllBeneficiaries = fn($values) => array_map($extractValue, $values);

        return array_values(array_map($extractValueFromAllBeneficiaries, $this->object->getContent()));
    }

    public function getStatus(): string
    {
        return $this->object->getState();
    }
}
