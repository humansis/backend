<?php

declare(strict_types=1);

namespace Enum;

final class SelectionCriteriaTarget
{
    use EnumTrait;

    public const BENEFICIARY = 'Beneficiary';
    public const HOUSEHOLD_HEAD = 'Head';
    public const HOUSEHOLD = 'Household';

    public static function values(): array
    {
        return [
            self::BENEFICIARY,
            self::HOUSEHOLD_HEAD,
            self::HOUSEHOLD,
        ];
    }
}
