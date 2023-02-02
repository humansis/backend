<?php

declare(strict_types=1);

namespace InputType\FilterFragment;

use Enum\AssistanceState;
use Symfony\Component\Validator\Constraints as Assert;

trait AssistanceStateFilterTrait
{
    #[Assert\All([new Assert\Choice(callback: [AssistanceState::class, 'values'])])]
    protected array $states;

    public function hasStates(): bool
    {
        return $this->has('states');
    }

    public function getStates(): array
    {
        return $this->states;
    }
}
