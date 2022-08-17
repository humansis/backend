<?php
declare(strict_types=1);

namespace InputType\Deprecated;

use InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class NationalIdType implements InputTypeInterface
{
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    private $type;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    private $number;

    /**
     * @Assert\Choice(callback={"\Enum\NationalIdType", "values"}, strict=true, groups={"Strict"})
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type ? \Enum\NationalIdType::valueFromAPI($this->type) : null;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getNumber(): ?string
    {
        return $this->number;
    }

    /**
     * @param string|null $number
     */
    public function setNumber(?string $number): void
    {
        $this->number = $number;
    }

    public function isEmpty(): bool
    {
        return $this->getNumber() === null && $this->getType() === null;
    }
}
