<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

class CountrySpecificUpdateInputType implements \CommonBundle\InputType\InputTypeInterface
{
    /**
     * @var string
     * @Assert\LessThanOrEqual(45)
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $field;

    /**
     * @var string
     * @Assert\NotBlank
     * @Assert\NotNull
     * @Assert\Choice({"number", "text"})
     */
    private $type;

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @param string $field
     */
    public function setField(string $field): void
    {
        $this->field = $field;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
