<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints\DoesNotContainComma;

class CountrySpecificUpdateInputType implements InputTypeInterface
{
    #[Assert\Type('string')]
    #[Assert\Length(max: 45)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[DoesNotContainComma]
    private string $field;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Choice(['number', 'text'])]
    private string $type;

    public function getField(): string
    {
        return trim($this->field);
    }

    public function setField($field): void
    {
        $this->field = $field;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType($type): void
    {
        $this->type = $type;
    }
}
