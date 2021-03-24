<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"ProductFilterInputType", "Strict"})
 */
class ProductFilterInputType extends AbstractFilterInputType
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

    public function hasIds(): bool
    {
        return $this->has('id');
    }

    /**
     * @return int[]
     */
    public function getIds(): array
    {
        return $this->id;
    }

    /**
     * @Assert\Type("string")
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
