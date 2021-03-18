<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;

class InstitutionFilterInputType extends AbstractFilterInputType
{
    /**
     * @var string
     * @Assert\Type("string")
     */
    protected $fulltext;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $projects;

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

    /**
     * @return int[]
     */
    public function getProjects()
    {
        return $this->projects;
    }

    public function hasProjects(): bool
    {
        return $this->has('projects');
    }
}
