<?php

declare(strict_types=1);

namespace Component\Assistance\DTO;

use Doctrine\Common\Collections\Collection;
use Entity\DivisionGroup;

class DivisionSummary
{
    /**
     * @param \Entity\DivisionGroup[]|Collection|null $divisionGroups
     */
    public function __construct(private readonly ?string $division, private readonly ?Collection $divisionGroups)
    {
    }

    public function getDivision(): ?string
    {
        return $this->division;
    }

    /**
     * @return DivisionGroup[]|Collection|null
     */
    public function getDivisionGroups(): ?Collection
    {
        return $this->divisionGroups;
    }
}
