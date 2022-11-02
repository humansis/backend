<?php

declare(strict_types=1);

namespace InputType;

use InputType\FilterFragment\FulltextFilterTrait;
use InputType\FilterFragment\PrimaryIdFilterTrait;
use InputType\FilterFragment\ProjectFilterTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Request\FilterInputType\AbstractFilterInputType;

#[Assert\GroupSequence(['CommunityFilterType', 'Strict'])]
class CommunityFilterType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
    use FulltextFilterTrait;
    use ProjectFilterTrait;
}
