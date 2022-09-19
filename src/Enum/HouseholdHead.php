<?php

declare(strict_types=1);

namespace Enum;

class HouseholdHead
{
    use EnumTrait {
        valueFromAPI as private parentValueFromAPI;
    }

    public const TRUE = 1;
    public const FALSE = 0;

    protected static $values = [
        1 => self::TRUE,
        0 => self::FALSE,
    ];

    public static function values(): array
    {
        return self::$values;
    }

    public static function valueFromAPI($APIValue)
    {
        return (bool) self::parentValueFromAPI($APIValue);
    }

    public static function apiAlternatives(): array
    {
        return [
            self::TRUE => array_merge(['head', 'h'], VariableBool::apiAlternatives()[VariableBool::TRUE]),
            self::FALSE => array_merge(['member', 'm'], VariableBool::apiAlternatives()[VariableBool::FALSE]),
        ];
    }
}