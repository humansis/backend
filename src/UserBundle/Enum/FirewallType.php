<?php declare(strict_types=1);

namespace UserBundle\Enum;

final class FirewallType implements Enum
{
    public const
        JWT = 'jwt',
        WSSE = 'wsse';

    public static function values(): array
    {
        return [
            self::JWT,
            self::WSSE,
        ];
    }
}
