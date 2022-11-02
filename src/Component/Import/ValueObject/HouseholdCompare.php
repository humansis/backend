<?php

declare(strict_types=1);

namespace Component\Import\ValueObject;

use Entity;
use InputType\HouseholdCreateInputType;

class HouseholdCompare
{
    public function __construct(private readonly HouseholdCreateInputType $imported, private readonly Entity\Household $current)
    {
    }

    public function getImported(): HouseholdCreateInputType
    {
        return $this->imported;
    }

    public function getCurrent(): Entity\Household
    {
        return $this->current;
    }
}
