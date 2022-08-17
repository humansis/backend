<?php
declare(strict_types=1);

namespace DBAL;

use Enum\SynchronizationBatchValidationType;

class SynchronizationBatchValidationTypeEnum extends \DBAL\AbstractEnum
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
