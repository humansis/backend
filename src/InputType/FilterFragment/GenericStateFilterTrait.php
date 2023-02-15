<?php

declare(strict_types=1);

namespace InputType\FilterFragment;

use Enum\SourceType;
use Symfony\Component\Validator\Constraints as Assert;

trait GenericStateFilterTrait
{
    /**
     * TODO: add validation from enum
     */
    #[Assert\All(constraints: [
        new Assert\Type('string', groups: ['Strict']),
    ], groups: ['Strict'])]
    #[Assert\Type('array')]
    protected $states;

    abstract protected function availableStates(): array;

    public function hasStates(): bool
    {
        return $this->has('states');
    }

    public function getStates()
    {
        return $this->states;
    }
}
