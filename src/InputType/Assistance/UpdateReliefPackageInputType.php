<?php

declare(strict_types=1);

namespace InputType\Assistance;

use Enum\ReliefPackageState;
use Request\InputTypeInterface;
use Validator\Constraints\Enum;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateReliefPackageInputType implements InputTypeInterface
{
    #[Enum(options: [
        'enumClass' => ReliefPackageState::class,
    ])]
    private $state;

    #[Assert\Type(type: 'scalar')]
    private $amountDistributed;

    #[Assert\Type(type: 'string')]
    #[Assert\NotBlank(allowNull: true)]
    private $notes;

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getAmountDistributed(): mixed
    {
        return $this->amountDistributed;
    }

    public function setAmountDistributed(mixed $amountDistributed): void
    {
        $this->amountDistributed = $amountDistributed;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }
}
