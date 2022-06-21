<?php declare(strict_types=1);

namespace NewApiBundle\Mapper\Assistance;

use DistributionBundle\Entity\AssistanceSelection;
use NewApiBundle\Entity\Assistance\SelectionCriteria;
use NewApiBundle\Enum\SelectionCriteriaField;
use NewApiBundle\Serializer\MapperInterface;

class SelectionCriteriaMapper implements MapperInterface
{
    /** @var SelectionCriteria */
    private $object;

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

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.SelectionCriteria::class.', '.get_class($object).' given.');
    }

    private function isGenderCriterium(): bool
    {
        return SelectionCriteriaField::GENDER === $this->object->getFieldString()
            || SelectionCriteriaField::HEAD_OF_HOUSEHOLD_GENDER === $this->object->getFieldString();
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

    public function getValue(): string
    {
        if ($this->isGenderCriterium()) {
            return (1 == $this->object->getValueString()) ? 'M' : 'F';
        }
        return $this->object->getValueString();
    }

    public function getWeight(): int
    {
        return $this->object->getWeight();
    }
}
