<?php

declare(strict_types=1);

namespace InputType;

use InputType\FilterFragment\PrimaryIdFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['SmartcardDepositFilterInputType', 'Strict'])]
class SmartcardDepositFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
}
