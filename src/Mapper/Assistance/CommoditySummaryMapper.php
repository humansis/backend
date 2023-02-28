<?php

declare(strict_types=1);

namespace Mapper\Assistance;

use Entity;
use Component\Assistance\AssistanceFactory;
use Component\Assistance\Domain;
use InvalidArgumentException;
use Utils\AssistanceService;
use Component\Assistance\DTO\CommoditySummary;
use Serializer\MapperInterface;

class CommoditySummaryMapper implements MapperInterface
{
    private ?\Component\Assistance\DTO\CommoditySummary $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return ($object instanceof CommoditySummary)
            && isset($context[self::NEW_API])
            && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof CommoditySummary) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . CommoditySummary::class . ', ' . $object::class . ' given.'
        );
    }

    public function getModalityType(): string
    {
        return $this->object->getModalityType();
    }

    public function getUnit(): string
    {
        return $this->object->getUnit();
    }

    public function getValue(): float
    {
        return $this->object->getAmount();
    }
}
