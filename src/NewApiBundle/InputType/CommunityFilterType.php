<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\InputType\FilterFragment\FulltextFilterTrait;
use NewApiBundle\InputType\FilterFragment\PrimaryIdFilterTrait;
use NewApiBundle\InputType\FilterFragment\ProjectFilterTrait;
use Symfony\Component\Validator\Constraints as Assert;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;

/**
 * @Assert\GroupSequence({"CommunityFilterType", "Strict"})
 */
class CommunityFilterType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
    use FulltextFilterTrait;
    use ProjectFilterTrait;
}
