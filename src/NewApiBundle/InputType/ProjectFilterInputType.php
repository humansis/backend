<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"ProjectFilterInputType", "Strict"})
 */
class ProjectFilterInputType extends AbstractFilterInputType
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
     * @Assert\Type("scalar")
     */
    protected $fulltext;

    public function hasIds(): bool
    {
        return $this->has('id');
    }

    public function getIds(): array
    {
        return $this->id;
    }

    public function hasFulltext(): bool
    {
        return $this->has('fulltext');
    }

    public function getFulltext()
    {
        return $this->fulltext;
    }
}
