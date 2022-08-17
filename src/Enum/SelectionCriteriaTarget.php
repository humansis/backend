<?php
declare(strict_types=1);

namespace Enum;

final class SelectionCriteriaTarget
{
    use EnumTrait;

    const BENEFICIARY = 'Beneficiary';
    const HOUSEHOLD_HEAD = 'Head';
    const HOUSEHOLD = 'Household';

    public static function values(): array
    {
        return [
            self::BENEFICIARY,
            self::HOUSEHOLD_HEAD,
            self::HOUSEHOLD,
        ];
    }
}
