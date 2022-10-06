<?php

declare(strict_types=1);

namespace Enum;

final class PhoneTypes
{
    use EnumTrait;

    public const LANDLINE = 'Landline';
    public const MOBILE = 'Mobile';

    public static function values(): array
    {
        return [
            self::LANDLINE,
            self::MOBILE,
        ];
    }

    protected static function apiMap(): array
    {
        return [
            self::LANDLINE => self::LANDLINE,
            self::MOBILE => self::MOBILE,
        ];
    }
}
