<?php

declare(strict_types=1);

namespace Enum;

class RoleType
{
    use EnumTrait;

    public const
        REPORTING = 'ROLE_REPORTING',
        PROJECT_MANAGEMENT = 'ROLE_PROJECT_MANAGEMENT',
        BENEFICIARY_MANAGEMENT = 'ROLE_BENEFICIARY_MANAGEMENT',
        USER_MANAGEMENT = 'ROLE_USER_MANAGEMENT',
        AUTHORISE_PAYMENT = 'ROLE_AUTHORISE_PAYMENT',
        READ_ONLY = 'ROLE_READ_ONLY',
        FIELD_OFFICER = 'ROLE_FIELD_OFFICER',
        PROJECT_OFFICER = 'ROLE_PROJECT_OFFICER',
        PROJECT_MANAGER = 'ROLE_PROJECT_MANAGER',
        COUNTRY_MANAGER = 'ROLE_COUNTRY_MANAGER',
        REGIONAL_MANAGER = 'ROLE_REGIONAL_MANAGER',
        ADMIN = 'ROLE_ADMIN',
        VENDOR = 'ROLE_VENDOR',
        ENUMERATOR = 'ROLE_ENUMERATOR';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [
            self::REPORTING,
            self::PROJECT_MANAGEMENT,
            self::BENEFICIARY_MANAGEMENT,
            self::USER_MANAGEMENT,
            self::AUTHORISE_PAYMENT,
            self::READ_ONLY,
            self::FIELD_OFFICER,
            self::PROJECT_OFFICER,
            self::PROJECT_MANAGER,
            self::COUNTRY_MANAGER,
            self::REGIONAL_MANAGER,
            self::ADMIN,
            self::VENDOR,
            self::ENUMERATOR,
        ];
    }
}
