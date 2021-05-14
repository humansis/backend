<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Enum\ImportQueueState;

class ImportStateQueueEnum extends \CommonBundle\DBAL\AbstractEnum
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
