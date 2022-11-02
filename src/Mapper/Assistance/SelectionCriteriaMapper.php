<?php

declare(strict_types=1);

namespace Mapper\Assistance;

use Component\Assistance\Domain\SelectionCriteria as SelectionCriteriaDomain;
use Component\Assistance\SelectionCriteriaFactory;
use Entity\Assistance\SelectionCriteria;
use InvalidArgumentException;
use Serializer\MapperInterface;

class SelectionCriteriaMapper implements MapperInterface
{
    private ?\Entity\Assistance\SelectionCriteria $object = null;

    private ?SelectionCriteriaDomain $criteriaDomain = null;

    public function __construct(private readonly SelectionCriteriaFactory $criteriaFactory)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof SelectionCriteria && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof SelectionCriteria) {
            $this->object = $object;
            $this->criteriaDomain = $this->criteriaFactory->hydrate($object);

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . SelectionCriteria::class . ', ' . $object::class . ' given.'
        );
    }

    private function isGenderCriterium(): bool
    {
        return 'gender' === $this->object->getFieldString()
            || 'headOfHouseholdGender' === $this->object->getFieldString();
    }

    public function getGroup(): int
    {
        return $this->object->getGroupNumber();
    }

    public function getTarget(): string
    {
        return $this->object->getTarget();
    }

    public function getField(): string
    {
        if ($this->isGenderCriterium()) {
            return 'gender';
        }

        return $this->object->getFieldString();
    }

    public function getCondition(): string
    {
        return $this->object->getConditionString();
    }

    public function getValue()
    {
        if ($this->isGenderCriterium()) {
            return (1 == $this->object->getValueString()) ? 'M' : 'F';
        }

        return $this->criteriaDomain->getTypedValue();
    }

    public function getWeight(): int
    {
        return $this->object->getWeight();
    }

    public function getDeprecated(): bool
    {
        return $this->object->isDeprecated();
    }
}
