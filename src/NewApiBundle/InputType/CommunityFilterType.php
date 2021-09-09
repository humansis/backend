<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\InputType\FilterFragment\FulltextFilterTrait;
use NewApiBundle\InputType\FilterFragment\PrimaryIdFilterTrait;
use Symfony\Component\Validator\Constraints as Assert;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;

/**
 * @Assert\GroupSequence({"CommunityFilterType", "Strict"})
 */
class CommunityFilterType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
    use FulltextFilterTrait;

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

    public function hasProjects(): bool
    {
        return $this->has('projects');
    }

    public function getProjects()
    {
        return $this->projects;
    }
}
