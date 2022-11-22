<?php

declare(strict_types=1);

namespace Enum;

final class BeneficiaryType
{
    use EnumTrait;

    public const HOUSEHOLD = 'Household';
    public const BENEFICIARY = 'Beneficiary';
    public const COMMUNITY = 'Community';
    public const INSTITUTION = 'Institution';

    public static function values(): array
    {
        return [
            self::HOUSEHOLD,
            self::BENEFICIARY,
            self::COMMUNITY,
            self::INSTITUTION,
        ];
    }
}
