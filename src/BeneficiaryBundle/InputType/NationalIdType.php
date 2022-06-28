<?php
declare(strict_types=1);

namespace BeneficiaryBundle\InputType;

use CommonBundle\InputType\InputTypeInterface;
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
     * @var int
     * @Assert\Type("integer")
     */
    private $priority;

    /**
     * @Assert\Choice(callback={"\NewApiBundle\Enum\NationalIdType", "values"}, strict=true, groups={"Strict"})
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type ? \NewApiBundle\Enum\NationalIdType::valueFromAPI($this->type) : null;
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

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }



    public function isEmpty(): bool
    {
        return $this->getNumber() === null && $this->getType() === null;
    }
}
