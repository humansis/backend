<?php

declare(strict_types=1);

namespace Component\Assistance\DTO;

use Component\Assistance\Domain\SelectionCriteria;

class CriteriaGroup
{
    /** @var int */
    private $groupNumber;

    /** @var SelectionCriteria[] */
    private $criteria;

    /**
     * @param int $groupNumber
     * @param SelectionCriteria[] $criteria
     */
    public function __construct(int $groupNumber, array $criteria)
    {
        $this->groupNumber = $groupNumber;
        $this->criteria = $criteria;
    }

    /**
     * @return int
     */
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
