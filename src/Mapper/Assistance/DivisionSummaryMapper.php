<?php

declare(strict_types=1);

namespace Mapper\Assistance;

use Doctrine\Common\Collections\Collection;
use Component\Assistance\DTO\DivisionSummary;
use InvalidArgumentException;
use Serializer\MapperInterface;

class DivisionSummaryMapper implements MapperInterface
{
    private ?\Component\Assistance\DTO\DivisionSummary $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof DivisionSummary && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof DivisionSummary) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . DivisionSummary::class . ', ' . $object::class . ' given.'
        );
    }

    public function getCode(): ?string
    {
        return $this->object->getDivision();
    }

    public function getQuantities(): ?Collection
    {
        $divisionGroups = $this->object->getDivisionGroups();

        return (
            $divisionGroups instanceof Collection
            && $this->object->getDivisionGroups()->count()
        )
            ? $this->object->getDivisionGroups()
            : null;
    }
}
