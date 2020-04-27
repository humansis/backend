<?php
namespace CommonBundle\InputType;

class DataTableFilterType implements InputTypeInterface
{
    public $pageIndex;
    public $pageSize;
    public $filter;
    public $sort;
}