<?php
declare(strict_types=1);

namespace DBAL;

use Enum\ImportQueueState;

class ImportStateQueueEnum extends \DBAL\AbstractEnum
{
    public function getName(): string
    {
        return 'enum_import_queue_state';
    }

    public static function all(): array
    {
        return ImportQueueState::values();
    }
}
