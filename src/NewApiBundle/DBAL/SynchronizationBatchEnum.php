<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Enum\SourceType;
use NewApiBundle\Enum\SynchronizationBatchState;

class SynchronizationBatchEnum extends \CommonBundle\DBAL\AbstractEnum
{
    public function getName(): string
    {
        return 'enum_synchronization_batch_state';
    }

    public static function all(): array
    {
        return SynchronizationBatchState::values();
    }
}
