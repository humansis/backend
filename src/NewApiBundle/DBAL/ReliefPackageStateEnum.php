<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Enum\ReliefPackageState;

class ReliefPackageStateEnum extends \CommonBundle\DBAL\AbstractEnum
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
