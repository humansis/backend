<?php
declare(strict_types=1);

namespace InputType;

use InputType\FilterFragment\FulltextFilterTrait;
use InputType\FilterFragment\PrimaryIdFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"ProjectFilterInputType", "Strict"})
 */
class ProjectFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
    use FulltextFilterTrait;
}
