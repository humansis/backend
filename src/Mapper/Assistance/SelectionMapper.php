<?php

declare(strict_types=1);

namespace Mapper\Assistance;

use Entity\AssistanceSelection;
use InvalidArgumentException;
use Serializer\MapperInterface;

class SelectionMapper implements MapperInterface
{
    private ?\Entity\AssistanceSelection $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof AssistanceSelection && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof AssistanceSelection) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . AssistanceSelection::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getThreshold(): ?int
    {
        return $this->object->getThreshold();
    }
}
