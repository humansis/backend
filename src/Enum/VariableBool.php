<?php

declare(strict_types=1);

namespace Enum;

class VariableBool
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

    public static function apiAlternatives(): array
    {
        return [
            self::TRUE => [1, 'true', 'truth', 'T', 'Y', 'yes', 'pravda', true],
            self::FALSE => [0, 'false', 'F', 'N', 'no', 'nepravda', false],
        ];
    }

    public static function valueFromAPI(int|string|bool $APIValue): int|string|bool
    {
        return (bool) self::parentValueFromAPI($APIValue);
    }
}
