<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class BeneficiaryType
{
    use EnumTrait;

    const HOUSEHOLD = 'Household';
    const BENEFICIARY = 'Beneficiary';
    const COMMUNITY = 'Community';
    const INSTITUTION = 'Institution';

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
