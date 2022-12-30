<?php

declare(strict_types=1);

namespace InputType;

use Enum\AssistanceState;
use Enum\AssistanceType;
use InputType\FilterFragment\LocationFilterTrait;
use InputType\FilterFragment\ModalityTypeFilterTrait;
use InputType\FilterFragment\PrimaryIdFilterTrait;
use InputType\FilterFragment\ProjectFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class AssistanceFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
    use ProjectFilterTrait;
    use LocationFilterTrait;
    use ModalityTypeFilterTrait;

    #[Assert\Type('boolean')]
    protected bool $upcoming;

    #[Assert\Choice(callback: [AssistanceType::class, 'values'])]
    protected string $type;

    #[Assert\All([new Assert\Choice(callback: [AssistanceState::class, 'values'])])]
    protected array $states;

    public function hasUpcomingOnly(): bool
    {
        return $this->has('upcoming');
    }

    public function getUpcomingOnly(): bool
    {
        return $this->upcoming;
    }

    public function hasType(): bool
    {
        return $this->has('type');
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function hasStates(): bool
    {
        return $this->has('states');
    }

    public function getStates(): array
    {
        return $this->states;
    }
}
