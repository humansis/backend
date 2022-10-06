<?php

declare(strict_types=1);

namespace DBAL;

use Enum\ReliefPackageState;

class ReliefPackageStateEnum extends AbstractEnum
{
    public function getName(): string
    {
        return 'enum_relief_package_state';
    }

    public static function all(): array
    {
        return ReliefPackageState::values();
    }
}
