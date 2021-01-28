<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"HouseholdCreateInputType", "Strict"})
 */
class HouseholdCreateInputType extends HouseholdUpdateInputType
{

}
