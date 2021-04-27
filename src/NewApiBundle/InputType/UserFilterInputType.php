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
     * @Assert\Type("scalar")
     */
    protected $fulltext;

    /**
     * @Assert\Choice({"true", "false"})
     */
    protected $showVendors;

    public function hasIds(): bool
    {
        return $this->has('id');
    }

    public function getIds(): array
    {
        return $this->id;
    }

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

    /**
     * @return bool
     */
    public function getShowVendors()
    {
        return "true" === $this->showVendors;
    }

    /**
     * @return bool
     */
    public function hasShowVendors(): bool
    {
        return $this->has('showVendors');
    }
}
