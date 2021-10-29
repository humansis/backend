<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\SynchronizationBatch;

use NewApiBundle\Enum\SynchronizationBatchState;
use NewApiBundle\Enum\SynchronizationBatchValidationType;
use NewApiBundle\InputType\FilterFragment\DateIntervalFilterTrait;
use NewApiBundle\InputType\FilterFragment\FulltextFilterTrait;
use NewApiBundle\InputType\FilterFragment\GenericStateFilterTrait;
use NewApiBundle\InputType\FilterFragment\GenericTypeFilterTrait;
use NewApiBundle\InputType\FilterFragment\PrimaryIdFilterTrait;
use NewApiBundle\InputType\FilterFragment\SourceFilterTrait;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
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
