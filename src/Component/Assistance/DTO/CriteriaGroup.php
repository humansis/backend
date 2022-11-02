<?php

declare(strict_types=1);

namespace Component\Assistance\DTO;

use Component\Assistance\Domain\SelectionCriteria;

class CriteriaGroup
{
    /**
     * @param SelectionCriteria[] $criteria
     */
    public function __construct(private readonly int $groupNumber, private readonly array $criteria)
    {
    }

    public function getGroupNumber(): int
    {
        return $this->groupNumber;
    }

    /**
     * @return SelectionCriteria[]
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }
}
