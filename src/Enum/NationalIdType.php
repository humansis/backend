<?php

declare(strict_types=1);

namespace Enum;

final class NationalIdType
{
    use EnumTrait;

    public const NATIONAL_ID = 'National ID';
    public const TAX_NUMBER = 'Tax Number';
    public const PASSPORT = 'Passport';
    public const FAMILY = 'Family Registration';
    public const BIRTH_CERTIFICATE = 'Birth Certificate';
    public const DRIVERS_LICENSE = 'Driver’s License';
    public const CAMP_ID = 'Camp ID';
    public const SOCIAL_SERVICE_ID = 'Social Service Card';
    public const OTHER = 'Other';
    public const NONE = 'None';
    public const CIVIL_REGISTRATION_RECORD = 'Civil registration record';

    public static function values(): array
    {
        return [
            self::NATIONAL_ID,
            self::TAX_NUMBER,
            self::PASSPORT,
            self::FAMILY,
            self::BIRTH_CERTIFICATE,
            self::DRIVERS_LICENSE,
            self::CAMP_ID,
            self::SOCIAL_SERVICE_ID,
            self::OTHER,
            self::NONE,
            self::CIVIL_REGISTRATION_RECORD,
        ];
    }

    public static function apiAlternatives(): array
    {
        return [
            self::FAMILY => ['Family registry', 'Family Book'],
            self::NATIONAL_ID => ['Card ID'],
        ];
    }
}
