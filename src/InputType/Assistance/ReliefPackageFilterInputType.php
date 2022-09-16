<?php
declare(strict_types=1);

namespace InputType\Assistance;

use InputType\FilterFragment\PrimaryIdFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"ReliefPackageFilterInputType", "Strict"})
 */
class ReliefPackageFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
}
