<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\InputType\FilterFragment\AssistanceFilterTrait;
use NewApiBundle\InputType\FilterFragment\DateIntervalFilterTrait;
use NewApiBundle\InputType\FilterFragment\FulltextFilterTrait;
use NewApiBundle\InputType\FilterFragment\LocationFilterTrait;
use NewApiBundle\InputType\FilterFragment\ProjectFilterTrait;
use NewApiBundle\InputType\FilterFragment\VendorFilterTrait;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use NewApiBundle\Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"SmartcardPurchasedItemFilterInputType", "Strict"})
 */
class SmartcardPurchasedItemFilterInputType extends AbstractFilterInputType
{
    use FulltextFilterTrait;
    use ProjectFilterTrait;
    use VendorFilterTrait;
    use LocationFilterTrait;
    use DateIntervalFilterTrait;
    use AssistanceFilterTrait;
}
