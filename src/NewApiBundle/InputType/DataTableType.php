<?php
namespace NewApiBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

class DataTableType implements InputTypeInterface
{
    /**
     * @var int
     * @Assert\NotBlank()
     * @Assert\GreaterThanOrEqual(0)
     */
    public $pageIndex = 0;
    /**
     * @var int
     * @Assert\NotBlank()
     * @Assert\GreaterThan(0)
     */
    public $pageSize = 30;
    /**
     * @var DataTableFilterType[]
     * @ Assert\Valid(traverse=true)
     */
    public $filter = [];
    /**
     * @var DataTableSorterType
     * @ Assert\Valid()
     */
    public $sort = null;

    /**
     * @return DataTableFilterType[]
     */
    public function getFilter(): array
    {
        return $this->filter;
    }

    /**
     * @param DataTableFilterType[] $filter
     */
    public function setFilter(array $filter): void
    {
        $this->filter = $filter;
    }

    public function getLimitMinimum(): int
    {
        return $this->pageIndex*$this->pageSize;
    }

    /**
     * @return int
     */
    public function getPageIndex(): int
    {
        return $this->pageIndex;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * @return DataTableSorterType
     */
    public function getSort(): ?DataTableSorterType
    {
        return $this->sort;
    }

    /**
     * @param DataTableSorterType $sort
     */
    public function setSort(?DataTableSorterType $sort): void
    {
        $this->sort = $sort;
    }
}
