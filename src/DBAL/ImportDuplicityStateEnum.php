<?php

declare(strict_types=1);

namespace DBAL;

use Enum\ImportDuplicityState;

class ImportDuplicityStateEnum extends AbstractEnum
{
    public function getName(): string
    {
        return 'enum_import_duplicity_state';
    }

    public static function all(): array
    {
        return ImportDuplicityState::values();
    }
}
