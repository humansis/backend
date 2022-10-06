<?php

declare(strict_types=1);

namespace InputType;

use InputType\FilterFragment\FulltextFilterTrait;
use InputType\FilterFragment\PrimaryIdFilterTrait;
use InputType\FilterFragment\VendorFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"ProductFilterInputType", "Strict"})
 */
class ProductFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
    use FulltextFilterTrait;
    use VendorFilterTrait;
}
