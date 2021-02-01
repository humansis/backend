<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;

/**
 * Class CommunityFilterType
 * @package NewApiBundle\InputType
 */
class CommunityFilterType extends AbstractFilterInputType
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

    public function hasProjects(): bool
    {
        return $this->has('projects');
    }

    public function getProjects()
    {
        return $this->projects;
    }
}
