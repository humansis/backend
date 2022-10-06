<?php

declare(strict_types=1);

namespace InputType;

use InputType\FilterFragment\PrimaryIdFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"SmartcardPurchaseFilterInputType", "Strict"})
 */
class SmartcardPurchaseFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
}
