<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class NationalIdType
{
    use EnumTrait;

    const NATIONAL_ID = 'National ID';
    const PASSPORT = 'Passport';
    const FAMILY_REGISTRATION = 'Family Registration';
    const FAMILY_BOOK = 'Family Book';
    const BIRTH_CERTIFICATE = 'Birth Certificate';
    const DRIVERS_LICENSE = 'Driver’s License';
    const CAMP_ID = 'Camp ID';
    const SOCIAL_SERVICE_ID = 'Social Service Card';
    const OTHER = 'Other';
    const NONE = 'None';

    public static function values(): array
    {
        return [
            self::NATIONAL_ID,
            self::PASSPORT,
            self::FAMILY_REGISTRATION,
            self::FAMILY_BOOK,
            self::BIRTH_CERTIFICATE,
            self::DRIVERS_LICENSE,
            self::CAMP_ID,
            self::SOCIAL_SERVICE_ID,
            self::OTHER,
            self::NONE,
        ];
    }

    public static function apiAlternatives(): array
    {
        return [
            self::FAMILY_REGISTRATION => ['Family registry'],
        ];
    }

}
