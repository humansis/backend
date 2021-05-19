<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class ImportFilterInputType extends AbstractFilterInputType
{
    /**
     * @var string
     * @Assert\Type("scalar")
     */
    protected $fulltext;

    /**
     * @return string
     */
    public function getFulltext()
    {
        return $this->fulltext;
    }

    public function hasFulltext(): bool
    {
        return $this->has('fulltext');
    }
}