<?php declare(strict_types=1);

namespace NewApiBundle\Mapper\Assistance;

use NewApiBundle\Component\Assistance\Domain\SelectionCriteria as SelectionCriteriaDomain;
use NewApiBundle\Component\Assistance\SelectionCriteriaFactory;
use NewApiBundle\Entity\Assistance\SelectionCriteria;
use NewApiBundle\Serializer\MapperInterface;

class SelectionCriteriaMapper implements MapperInterface
{
    /** @var SelectionCriteria */
    private $object;

    /** @var SelectionCriteriaDomain */
    private $criteriaDomain;

    /**
     * @var SelectionCriteriaFactory
     */
    private $criteriaFactory;

    public function __construct(SelectionCriteriaFactory $criteriaFactory)
    {
        $this->criteriaFactory = $criteriaFactory;
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

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.SelectionCriteria::class.', '.get_class($object).' given.');
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
