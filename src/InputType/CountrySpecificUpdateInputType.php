<?php

declare(strict_types=1);

namespace InputType;

use Component\CSO\Enum\CountrySpecificType;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints\Enum;

class CountrySpecificUpdateInputType implements InputTypeInterface
{
    #[Assert\Type('string')]
    #[Assert\Length(max: 45)]
    #[Assert\NotBlank]
    private $field;

    #[Assert\NotBlank]
    #[Enum(options: [
        'enumClass' => CountrySpecificType::class,
    ])]
    private $type;

    #[Assert\Type('bool')]
    #[Assert\NotNull]
    private $multiValue;

    /**
     * @return string
     */
    public function getField()
    {
        return trim((string) $this->field);
    }

    public function setField(mixed $field)
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

    public function setType(mixed $type)
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function getMultiValue()
    {
        return $this->multiValue;
    }

    public function setMultiValue(mixed $multiValue): void
    {
        $this->multiValue = $multiValue;
    }
}
