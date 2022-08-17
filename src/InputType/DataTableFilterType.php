<?php

namespace InputType;

use Symfony\Component\Validator\Constraints as Assert;

class DataTableFilterType implements InputTypeInterface
{
    /**
     * @var string
     * @Assert\NotBlank()
     */
    public $category;

    /**
     * @var string|array
     * @Assert\NotBlank()
     */
    public $filter;
}
