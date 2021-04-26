<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;

/**
 * @Assert\GroupSequence({"CommunityFilterType", "Strict"})
 */
class CommunityFilterType extends AbstractFilterInputType
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

    /**
     * @return bool
     */
    public function hasFulltext(): bool
    {
        return $this->has('fulltext');
    }

    public function hasProjects(): bool
    {
        return $this->has('projects');
    }

    public function getProjects()
    {
        return $this->projects;
    }
}
