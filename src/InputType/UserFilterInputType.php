<?php

namespace InputType;

use InputType\FilterFragment\FulltextFilterTrait;
use InputType\FilterFragment\PrimaryIdFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
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
