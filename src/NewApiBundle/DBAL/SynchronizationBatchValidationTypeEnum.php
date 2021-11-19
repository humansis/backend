<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Enum\SourceType;
use NewApiBundle\Enum\SynchronizationBatchState;
use NewApiBundle\Enum\SynchronizationBatchValidationType;

class SynchronizationBatchValidationTypeEnum extends \CommonBundle\DBAL\AbstractEnum
{
    public function getName(): string
    {
        return 'enum_synchronization_batch_validation_type';
    }

    public static function all(): array
    {
        return SynchronizationBatchValidationType::values();
    }
}
