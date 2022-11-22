<?php

declare(strict_types=1);

namespace DBAL;

use Enum\SynchronizationBatchState;

class SynchronizationBatchEnum extends AbstractEnum
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
