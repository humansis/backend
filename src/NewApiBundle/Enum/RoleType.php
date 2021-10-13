<?php declare(strict_types=1);

namespace NewApiBundle\Enum;

class RoleType
{
    public const ADMIN = 'ROLE_ADMIN';

    public static function values(): array
    {
        return [self::ADMIN];
    }
}
