<?php

declare(strict_types=1);

namespace Enum;

class HouseholdHead
{
    use EnumTrait {
        valueFromAPI as private parentValueFromAPI;
    }

    final public const TRUE = 1;
    final public const FALSE = 0;

    protected static $values = [
        1 => self::TRUE,
        0 => self::FALSE,
    ];

    public static function values(): array
    {
        return self::$values;
    }

    public static function valueFromAPI(bool $APIValue): bool
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
