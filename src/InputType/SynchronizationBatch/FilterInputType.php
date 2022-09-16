<?php
declare(strict_types=1);

namespace InputType\SynchronizationBatch;

use Enum\SynchronizationBatchState;
use Enum\SynchronizationBatchValidationType;
use InputType\FilterFragment\DateIntervalFilterTrait;
use InputType\FilterFragment\FulltextFilterTrait;
use InputType\FilterFragment\GenericStateFilterTrait;
use InputType\FilterFragment\GenericTypeFilterTrait;
use InputType\FilterFragment\PrimaryIdFilterTrait;
use InputType\FilterFragment\SourceFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"FilterInputType", "Strict"})
 */
class FilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
    use FulltextFilterTrait;
    use SourceFilterTrait;
    use GenericStateFilterTrait;
    use GenericTypeFilterTrait;
    use DateIntervalFilterTrait;

    protected function availableStates(): array
    {
        return SynchronizationBatchState::values();
    }

    protected function availableTypes(): array
    {
        return SynchronizationBatchValidationType::values();
    }
}
