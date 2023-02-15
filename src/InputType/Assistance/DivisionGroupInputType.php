<?php

declare(strict_types=1);

namespace InputType\Assistance;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DivisionGroupInputType implements InputTypeInterface
{
    #[Assert\Type('int')]
    #[Assert\Range(notInRangeMessage: 'Supported range is from {{ min }} to {{ max }} members.', min: 1, max: 1000)]
    private ?int $rangeFrom = null;

    #[Assert\Type(type: 'int')]
    #[Assert\Range(notInRangeMessage: 'Supported range is from {{ min }} to {{ max }} members.', min: 1, max: 1000)]
    #[Assert\NotBlank(allowNull: true)]
    private ?int $rangeTo = null;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $value;

    public function getRangeFrom(): int
    {
        return $this->rangeFrom;
    }

    public function setRangeFrom(int $rangeFrom): void
    {
        $this->rangeFrom = $rangeFrom;
    }

    public function getRangeTo(): ?int
    {
        return $this->rangeTo;
    }

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

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}
