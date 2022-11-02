<?php

namespace Enum;

class HouseholdAssets
{
    use EnumTrait;

    final public const AC = 'A/C';
    final public const AGRICULTURAL_LAND = 'Agricultural Land';
    final public const CAR = 'Car';
    final public const FLATSCREEN_TV = 'Flatscreen TV';
    final public const LIVESTOCK = 'Livestock';
    final public const MOTORBIKE = 'Motorbike';
    final public const WASHING_MACHINE = 'Washing Machine';

    protected static $values = [
        0 => self::AC,
        1 => self::AGRICULTURAL_LAND,
        2 => self::CAR,
        3 => self::FLATSCREEN_TV,
        4 => self::LIVESTOCK,
        5 => self::MOTORBIKE,
        6 => self::WASHING_MACHINE,
    ];

    public static function values(): array
    {
        return array_values(self::$values);
    }

    public static function apiAlternatives(): array
    {
        return [
            self::AC => [0],
            self::AGRICULTURAL_LAND => [1],
            self::CAR => [2],
            self::FLATSCREEN_TV => [3],
            self::LIVESTOCK => [4],
            self::MOTORBIKE => [5],
            self::WASHING_MACHINE => [6],
        ];
    }
}
