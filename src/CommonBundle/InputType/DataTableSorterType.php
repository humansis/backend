<?php
namespace CommonBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

class DataTableSorterType implements InputTypeInterface
{
    /**
     * @var string
     * @Assert\NotBlank()
     */
    public $sort;
    /**
     * @var string
     * @Assert\NotBlank()
     */
    public $direction;
}