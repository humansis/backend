<?php

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class UserFilterInputType extends AbstractFilterInputType
{
    /**
     * @var string
     * @Assert\Type("string")
     */
    protected $fulltext;

    /**
     * @return string
     */
    public function getFulltext(): string
    {
        return $this->fulltext;
    }

    /**
     * @return bool
     */
    public function hasFulltext(): bool
    {
        return $this->has('fulltext');
    }
}
