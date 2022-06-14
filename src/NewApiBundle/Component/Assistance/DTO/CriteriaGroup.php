<?php declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\DTO;

use NewApiBundle\Component\Assistance\Domain\SelectionCriteria;

class CriteriaGroup
{
    /** @var integer */
    private $groupNumber;
    /** @var SelectionCriteria[] */
    private $criteria;

    /**
     * @param int                 $groupNumber
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
