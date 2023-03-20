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

    public function getState()
    {
        return $this->state;
    }

    public function setState($state): void
    {
        $this->state = $state;
    }

    public function getAmountDistributed()
    {
        return $this->amountDistributed;
    }

    public function setAmountDistributed($amountDistributed): void
    {
        $this->amountDistributed = $amountDistributed;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    public function setNotes($notes): void
    {
        $this->notes = $notes;
    }
}
