<?php

declare(strict_types=1);

namespace InputType\Deprecated;

use InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class NationalIdType implements InputTypeInterface
{
    #[Assert\Length(max: 255)]
    private ?string $type = null;

    #[Assert\Length(max: 255)]
    private ?string $number = null;

    #[Assert\Type('integer')]
    private ?int $priority = null;

    #[Assert\Choice(callback: [\Enum\NationalIdType::class, 'values'], strict: true, groups: ['Strict'])]
    public function getType(): ?string
    {
        return $this->type ? \Enum\NationalIdType::valueFromAPI($this->type) : null;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): void
    {
        $this->number = $number;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function isEmpty(): bool
    {
        return $this->getNumber() === null && $this->getType() === null;
    }
}
