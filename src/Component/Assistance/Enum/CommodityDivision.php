<?php
declare(strict_types=1);

namespace Component\Assistance\Enum;


use Enum\EnumTrait;

final class CommodityDivision
{
    use EnumTrait;

    public const PER_HOUSEHOLD = 'Per Household';
    public const PER_HOUSEHOLD_MEMBER = 'Per Household Member';

    public static function values(): array
    {
        return [
            self::PER_HOUSEHOLD,
            self::PER_HOUSEHOLD_MEMBER,
        ];
    }
}