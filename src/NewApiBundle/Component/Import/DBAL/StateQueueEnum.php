<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\DBAL;

use NewApiBundle\Component\Import\Enum\QueueState;

class StateQueueEnum extends \CommonBundle\DBAL\AbstractEnum
{
    public function getName(): string
    {
        return 'enum_import_queue_state';
    }

    public static function all(): array
    {
        return QueueState::values();
    }
}
