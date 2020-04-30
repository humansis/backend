<?php
namespace CommonBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

class DataTableType implements InputTypeInterface
{
    /**
     * @var int
     * @Assert\NotBlank()
     * @Assert\GreaterThanOrEqual(0)
     */
    public $pageIndex;
    /**
     * @var int
     * @Assert\NotBlank()
     * @Assert\GreaterThan(0)
     */
    public $pageSize;
    /**
     * @var DataTableFilterType[]
     * @Assert\Valid(traverse=true)
     */
    public $filter;
    /**
     * @var DataTableSorterType
     * @Assert\Valid()
     */
    private $sort;

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



    /**
     * @return DataTableSorterType
     */
    public function getSort(): DataTableSorterType
    {
        return $this->sort;
    }

    /**
     * @param DataTableSorterType $sort
     */
    public function setSort(DataTableSorterType $sort): void
    {
        $this->sort = $sort;
    }
}
