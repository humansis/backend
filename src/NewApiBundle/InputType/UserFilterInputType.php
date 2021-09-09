<?php

namespace NewApiBundle\InputType;

use NewApiBundle\InputType\FilterFragment\FulltextFilterTrait;
use NewApiBundle\InputType\FilterFragment\PrimaryIdFilterTrait;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class UserFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
    use FulltextFilterTrait;

    /**
     * @Assert\Choice({"true", "false"})
     */
    protected $showVendors;

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
