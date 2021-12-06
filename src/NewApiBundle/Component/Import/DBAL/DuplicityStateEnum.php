<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\DBAL;

use NewApiBundle\Component\Import\Enum\DuplicityState;

class DuplicityStateEnum extends \CommonBundle\DBAL\AbstractEnum
{
    public function getName(): string
    {
        return 'enum_import_duplicity_state';
    }

    public static function all(): array
    {
        return DuplicityState::values();
    }
}
