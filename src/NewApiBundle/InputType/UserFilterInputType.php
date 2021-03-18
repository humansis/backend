<?php

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class UserFilterInputType extends AbstractFilterInputType
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
     * @Assert\Type("string")
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
}
