<?php

namespace Enum;

class HouseholdShelterStatus
{
    use EnumTrait;

    final public const TENT = 'Tent';
    final public const MAKESHIFT_SHELTER = 'Makeshift Shelter';
    final public const TRANSITIONAL_SHELTER = 'Transitional Shelter';
    final public const HOUSE_APARTMENT_SEVERELY_DAMAGED = 'House/Apartment - Severely Damaged';
    final public const HOUSE_APARTMENT_MODERATELY_DAMAGED = 'House/Apartment - Moderately Damaged';
    final public const HOUSE_APARTMENT_NOT_DAMAGED = 'House/Apartment - Not Damaged';
    final public const ROOM_OR_SPACE_IN_PUBLIC_BUILDING = 'Room or Space in Public Building';
    final public const ROOM_OR_SPACE_IN_UNFINISHED_BUILDING = 'Room or Space in Unfinished Building';
    final public const OTHER = 'Other';
    final public const HOUSE_APARTMENT_LIGHTLY_DAMAGED = 'House/Apartment - Lightly Damaged';

    protected static $values = [
        1 => self::TENT,
        2 => self::MAKESHIFT_SHELTER,
        3 => self::TRANSITIONAL_SHELTER,
        4 => self::HOUSE_APARTMENT_SEVERELY_DAMAGED,
        5 => self::HOUSE_APARTMENT_MODERATELY_DAMAGED,
        6 => self::HOUSE_APARTMENT_NOT_DAMAGED,
        7 => self::ROOM_OR_SPACE_IN_PUBLIC_BUILDING,
        8 => self::ROOM_OR_SPACE_IN_UNFINISHED_BUILDING,
        9 => self::OTHER,
        10 => self::HOUSE_APARTMENT_LIGHTLY_DAMAGED,
    ];

    public static function values(): array
    {
        return array_values(self::$values);
    }

    public static function apiAlternatives(): array
    {
        return [
            self::TENT => [1],
            self::MAKESHIFT_SHELTER => [2],
            self::TRANSITIONAL_SHELTER => [3],
            self::HOUSE_APARTMENT_SEVERELY_DAMAGED => [4],
            self::HOUSE_APARTMENT_MODERATELY_DAMAGED => [5],
            self::HOUSE_APARTMENT_NOT_DAMAGED => [6],
            self::ROOM_OR_SPACE_IN_PUBLIC_BUILDING => [7],
            self::ROOM_OR_SPACE_IN_UNFINISHED_BUILDING => [8],
            self::OTHER => [9],
            self::HOUSE_APARTMENT_LIGHTLY_DAMAGED => [10],
        ];
    }
}
