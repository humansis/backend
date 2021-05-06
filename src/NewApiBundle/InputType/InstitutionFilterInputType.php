<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;

/**
 * @Assert\GroupSequence({"InstitutionFilterInputType", "Strict"})
 */
class InstitutionFilterInputType extends AbstractFilterInputType
{
    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $id;

    /**
     * @var string
     * @Assert\Type("scalar")
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

    public function hasIds(): bool
    {
        return $this->has('id');
    }

    public function getIds(): array
    {
        return $this->id;
    }

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
