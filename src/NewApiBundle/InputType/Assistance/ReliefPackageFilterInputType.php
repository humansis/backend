<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\Assistance;

use NewApiBundle\InputType\FilterFragment\PrimaryIdFilterTrait;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"ReliefPackageFilterInputType", "Strict"})
 */
class ReliefPackageFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
}
