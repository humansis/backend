<?php

declare(strict_types=1);

namespace InputType\Assistance;

use Request\InputTypeInterface;
use Validator\Constraints\SelectionCriterionField;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SelectionCriterionField
 */
class SelectionCriterionInputType implements InputTypeInterface
{
    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $group;

    #[Assert\Choice(callback: [\Enum\SelectionCriteriaTarget::class, 'values'])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $target;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $field;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $condition;

    #[Assert\NotNull]
    private $value;

    #[Assert\Type('integer')]
    #[Assert\GreaterThan(0)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $weight;

    /**
     * @return int
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param int $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param string $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param string $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return mixed
     */
    public function getCondition()
    {
        return $this->condition;
    }

    public function setCondition(mixed $condition)
    {
        $this->condition = $condition;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function setValue(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param int $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }
}
