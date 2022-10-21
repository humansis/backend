<?php

declare(strict_types=1);

namespace InputType;

use InputType\FilterFragment\FulltextFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;

class ProjectsAssistanceFilterInputType extends AbstractFilterInputType
{
    use FulltextFilterTrait;

    /** @var array */
    protected $states;

    public function hasStates(): bool
    {
        return $this->has('states');
    }

    public function getStates(): array
    {
        return $this->states;
    }
}
