<?php
namespace CommonBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

class DataTableFilterType implements InputTypeInterface
{
    /**
     * @var string
     * @Assert\NotBlank()
     */
    public $category;
    /**
     * @var string
     * @Assert\NotBlank()
     */
    public $filter;
}