<?php

namespace InputType;

use Symfony\Component\Validator\Constraints as Assert;

class DataTableSorterType implements InputTypeInterface
{
    /**
     * @var string
     * FIXME: all or nothing
     * @ Assert\NotBlank()
     */
    public $sort;

    /**
     * @var string
     * @ Assert\NotBlank()
     */
    public $direction;
}
