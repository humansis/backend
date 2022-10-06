<?php

declare(strict_types=1);

namespace DBAL;

use Enum\ImportState;

class ImportStateEnum extends AbstractEnum
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
