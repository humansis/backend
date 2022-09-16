<?php declare(strict_types=1);

namespace InputType\Assistance;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DivisionGroupInputType implements InputTypeInterface
{
    /**
     * @var int
     * @Assert\Type("int")
     * @Assert\Range(min="1", max="1000", notInRangeMessage="Supported range is from {{ min }} to {{ max }} members.")
     */
    private $rangeFrom;

    /**
     * @var int|null
     * @Assert\Type(type="int")
     * @Assert\Range(min="1", max="1000", notInRangeMessage="Supported range is from {{ min }} to {{ max }} members.")
     * @Assert\NotBlank(allowNull=true)
     */
    private $rangeTo;

    /**
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $value;

    /**
     * @return int
     */
    public function getRangeFrom(): int
    {
        return $this->rangeFrom;
    }

    /**
     * @param int $rangeFrom
     */
    public function setRangeFrom(int $rangeFrom): void
    {
        $this->rangeFrom = $rangeFrom;
    }

    /**
     * @return int|null
     */
    public function getRangeTo(): ?int
    {
        return $this->rangeTo;
    }

    /**
     * @param int|null $rangeTo
     */
    public function setRangeTo(?int $rangeTo): void
    {
        $this->rangeTo = $rangeTo;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

}
