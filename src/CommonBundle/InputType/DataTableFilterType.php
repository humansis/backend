<?php
namespace CommonBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

class DataTableFilterType implements InputTypeInterface
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
     * @var mixed[]
     */
    public $filter;
    /**
     * @var DataTableSorterType
     */
    public $sort;
}