<?php

declare(strict_types=1);

namespace Mapper\Assistance\OfflineApp;

use Entity\Commodity;
use InvalidArgumentException;
use Serializer\MapperInterface;

class CommodityMapper implements MapperInterface
{
    /** @var Commodity */
    private $object;

    /** @var array */
    public $modality_type;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Commodity && isset($context[MapperInterface::OFFLINE_APP]) && true === $context[MapperInterface::OFFLINE_APP]
            && isset($context['version']) && $context['version'] === 'v1';
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Commodity) {
            $this->object = $object;

            //Workaround because of /offline-app/v1/projects/{id}/distribution API. And because of removed modality types from database.
            //Getters do not support snake case style. It is done properly in v2 of the endpoint.
            //This file (alongside with all code necessary for v1) should be removed in 3.9
            $this->modality_type = [
                'id' => 1,
                'name' => (string) $object->getModalityType(),
            ];

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Commodity::class . ', ' . get_class($object) . ' given.'
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
}
