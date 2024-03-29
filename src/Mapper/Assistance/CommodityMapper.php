<?php

declare(strict_types=1);

namespace Mapper\Assistance;

use Entity\Commodity;
use Component\Assistance\DTO\DivisionSummary;
use InvalidArgumentException;
use Serializer\MapperInterface;

class CommodityMapper implements MapperInterface
{
    private ?\Entity\Commodity $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Commodity && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Commodity) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Commodity::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
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
        return $this->object->getValue();
    }

    public function getDescription(): ?string
    {
        return $this->object->getDescription();
    }

    public function getDivision(): ?DivisionSummary
    {
        $summary = $this->object->getDivisionSummary();

        return $summary->getDivision() === null ? null : $summary;
    }
}
