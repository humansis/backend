<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\DBAL;

use NewApiBundle\Component\Import\Enum\State;

class StateEnum extends \CommonBundle\DBAL\AbstractEnum
{
    public function getName(): string
    {
        return 'enum_import_state';
    }

    public static function all(): array
    {
        return State::values();
    }
}
