<?php

declare(strict_types=1);

namespace Enum;

final class SourceType
{
    use EnumTrait;

    public const WEB_APP = 'Web';
    public const VENDOR_APP = 'Vendor';
    public const USER_APP = 'User';
    public const CLI = 'CLI';

    public static function values(): array
    {
        return [
            self::WEB_APP,
            self::VENDOR_APP,
            self::USER_APP,
            self::CLI,
        ];
    }
}
