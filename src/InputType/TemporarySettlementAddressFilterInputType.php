<?php

declare(strict_types=1);

namespace InputType;

use InputType\FilterFragment\PrimaryIdFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"TemporarySettlementAddressFilterInputType", "Strict"})
 */
class TemporarySettlementAddressFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
}
