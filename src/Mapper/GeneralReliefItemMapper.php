<?php

declare(strict_types=1);

namespace Mapper;

use DateTime;
use Entity\GeneralReliefItem;
use InvalidArgumentException;
use Serializer\MapperInterface;

class GeneralReliefItemMapper implements MapperInterface
{
    /** @var GeneralReliefItem */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof GeneralReliefItem &&
            isset($context[self::NEW_API]) && true === $context[self::NEW_API] &&
            !isset($context['offline-app']);
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof GeneralReliefItem) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException('Invalid argument. It should be instance of ' . GeneralReliefItem::class . ', ' . get_class($object) . ' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getDistributed(): bool
    {
        return null !== $this->object->getDistributedAt();
    }

    public function getDateOfDistribution(): ?string
    {
        return $this->object->getDistributedAt() ? $this->object->getDistributedAt()->format(DateTime::ISO8601) : null;
    }

    public function getNote(): ?string
    {
        return $this->object->getNotes();
    }
}
