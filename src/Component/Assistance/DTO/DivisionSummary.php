<?php

declare(strict_types=1);

namespace Component\Assistance\DTO;

use Doctrine\Common\Collections\Collection;
use Entity\DivisionGroup;

class DivisionSummary
{
    /**
     * @var string|null
     */
    private $division;

    /**
     * @var DivisionGroup[]|Collection
     */
    private $divisionGroups;

    /**
     * @param string|null $division
     * @param Collection $divisionGroups
     */
    public function __construct(?string $division, Collection $divisionGroups)
    {
        $this->division = $division;
        $this->divisionGroups = $divisionGroups;
    }

    /**
     * @return string|null
     */
    public function getDivision(): ?string
    {
        return $this->division;
    }

    /**
     * @return DivisionGroup[]|Collection
     */
    public function getDivisionGroups(): Collection
    {
        return $this->divisionGroups;
    }
}
