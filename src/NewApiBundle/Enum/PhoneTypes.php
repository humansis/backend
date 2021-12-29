<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class PhoneTypes
{
    use EnumTrait;

    const LANDLINE = 'Landline';
    const MOBILE = 'Mobile';

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
