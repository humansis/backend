<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class SourceType
{
    const WEB_APP = 'Web';
    const VENDOR_APP = 'Vendor';
    const USER_APP = 'User';
    const CLI = 'CLI';

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
