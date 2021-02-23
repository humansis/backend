<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"InstitutionFilterInputType", "Strict"})
 */
class InstitutionFilterInputType extends AbstractFilterInputType
{
    /**
     * @Assert\Type("scalar")
     */
    protected $fulltext;

    public function hasFulltext(): bool
    {
        return $this->has('fulltext');
    }

    public function getFulltext()
    {
        return $this->fulltext;
    }

}
