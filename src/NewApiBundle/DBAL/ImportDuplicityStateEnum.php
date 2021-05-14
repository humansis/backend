<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Enum\ImportDuplicityState;

class ImportDuplicityStateEnum extends \CommonBundle\DBAL\AbstractEnum
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
