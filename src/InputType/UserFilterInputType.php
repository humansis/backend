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

    #[Assert\Choice([true, false])]
    protected bool $showVendors;

    public function getShowVendors(): bool
    {
        return $this->showVendors;
    }

    public function hasShowVendors(): bool
    {
        return $this->has('showVendors');
    }
}
