<?php

declare(strict_types=1);

namespace InputType\FilterFragment;

use Symfony\Component\Validator\Constraints as Assert;
use Happyr\Validator\Constraint\EntityExist;

trait ProjectFilterTrait
{
    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"}),
     *         @EntityExist(entity="\Entity\Project", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
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
