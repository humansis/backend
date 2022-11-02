<?php

declare(strict_types=1);

namespace Mapper\Assistance\OfflineApp;

use Entity\Commodity;
use InvalidArgumentException;
use Serializer\MapperInterface;

class CommodityMapperV2 implements MapperInterface
{
    private ?\Entity\Commodity $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Commodity && isset($context[MapperInterface::OFFLINE_APP]) && true === $context[MapperInterface::OFFLINE_APP]
            && isset($context['version']) && $context['version'] === 'v2';
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

    public function getUnit(): string
    {
        return $this->object->getUnit();
    }

    public function getValue(): float
    {
        return $this->object->getValue();
    }

    public function getDescription(): string
    {
        return $this->object->getDescription();
    }

    public function getModalityType(): string
    {
        return $this->object->getModalityType();
    }
}
