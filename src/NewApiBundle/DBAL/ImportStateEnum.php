<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Enum\ImportState;

class ImportStateEnum extends \NewApiBundle\DBAL\AbstractEnum
{
    public function getName(): string
    {
        return 'enum_import_state';
    }

    public static function all(): array
    {
        return ImportState::values();
    }
}
