<?php

declare(strict_types=1);

namespace Mapper\Assistance;

use Entity\ScoringBlueprint;
use InvalidArgumentException;
use Serializer\MapperInterface;

class ScoringBlueprintMapper implements MapperInterface
{
    /** @var ScoringBlueprint */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return ($object instanceof ScoringBlueprint)
            && isset($context[self::NEW_API])
            && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof ScoringBlueprint) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException('Invalid argument. It should be instance of ' . ScoringBlueprint::class . ', ' . get_class($object) . ' given.');
    }

    public function getId()
    {
        return $this->object->getId();
    }

    public function getName()
    {
        return $this->object->getName();
    }

    public function getCreatedAt()
    {
        return $this->object->getCreatedAt();
    }

    public function isArchived()
    {
        return $this->object->isArchived();
    }
}
