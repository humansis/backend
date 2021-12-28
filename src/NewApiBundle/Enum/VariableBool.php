<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

class VariableBool
{
    use EnumTrait { valueFromAPI as private parentValueFromAPI; }

    const TRUE = 1;
    const FALSE = 0;

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

    public static function valueFromAPI($APIValue)
    {
        return (bool) self::parentValueFromAPI($APIValue);
    }

}
