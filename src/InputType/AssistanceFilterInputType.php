<?php

declare(strict_types=1);

namespace InputType;

use Enum\AssistanceType;
use InputType\FilterFragment\AssistanceStateFilterTrait;
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
    use AssistanceStateFilterTrait;

    #[Assert\Type('boolean')]
    protected bool $upcoming;

    #[Assert\Choice(callback: [AssistanceType::class, 'values'])]
    protected string $type;

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
}
