<?php

declare(strict_types=1);

namespace InputType;

use InputType\FilterFragment\AssistanceFilterTrait;
use InputType\FilterFragment\DateIntervalFilterTrait;
use InputType\FilterFragment\FulltextFilterTrait;
use InputType\FilterFragment\LocationFilterTrait;
use InputType\FilterFragment\ProjectFilterTrait;
use InputType\FilterFragment\VendorFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['SmartcardPurchasedItemFilterInputType', 'Strict'])]
class SmartcardPurchasedItemFilterInputType extends AbstractFilterInputType
{
    use FulltextFilterTrait;
    use ProjectFilterTrait;
    use VendorFilterTrait;
    use LocationFilterTrait;
    use DateIntervalFilterTrait;
    use AssistanceFilterTrait;
}
