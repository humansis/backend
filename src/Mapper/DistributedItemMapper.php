<?php

declare(strict_types=1);

namespace Mapper;

use DateTimeInterface;
use Entity\DistributedItem;
use InvalidArgumentException;
use Serializer\MapperInterface;

class DistributedItemMapper implements MapperInterface
{
    private ?\Entity\DistributedItem $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof DistributedItem && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof DistributedItem) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . DistributedItem::class . ', ' . $object::class . ' given.'
        );
    }

    public function getProjectId(): int
    {
        return $this->object->getProject()->getId();
    }

    public function getBeneficiaryId(): int
    {
        return $this->object->getBeneficiary()->getId();
    }

    public function getAssistanceId(): int
    {
        return $this->object->getAssistance()->getId();
    }

    public function getDateDistribution(): ?string
    {
        return $this->object->getDateDistribution()?->format(
            DateTimeInterface::ATOM
        );
    }

    public function getCommodityId(): int
    {
        return $this->object->getCommodity()->getId();
    }

    public function getAmount(): float
    {
        return $this->object->getAmount();
    }

    public function getSpent(): ?float
    {
        return $this->object->getSpent();
    }

    public function getLocationId(): int
    {
        return $this->object->getLocation()->getId();
    }

    public function getFullLocationNames(): string
    {
        return $this->object->getLocation()->getFullPathNames();
    }

    public function getCarrierNumber(): ?string
    {
        return $this->object->getCarrierNumber();
    }

    public function getType(): string
    {
        return $this->object->getBeneficiaryType();
    }

    public function getModalityType(): string
    {
        return $this->object->getModalityType();
    }

    public function getFieldOfficerId(): ?int
    {
        return $this->object->getFieldOfficer() ? $this->object->getFieldOfficer()->getId() : null;
    }
}
