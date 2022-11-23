<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CountrySpecificUpdateInputType implements InputTypeInterface
{
    #[Assert\Type('string')]
    #[Assert\Length(max: 45)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $field;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Choice(['number', 'text'])]
    private $type;

    /**
     * @return string
     */
    public function getField()
    {
        return trim((string) $this->field);
    }

    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return string one of number|text
     */
    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }
}
