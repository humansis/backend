<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class SelectionCriteriaTarget
{
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
