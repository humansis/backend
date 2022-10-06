<?php

declare(strict_types=1);

namespace DBAL;

use Enum\HouseholdShelterStatus;

class HouseholdShelterStatusEnum extends AbstractEnum
{
    use EnumTrait;

    // unused yet, prepared for future migration
    public function getName(): string
    {
        return 'enum_household_shelter_status';
    }

    public static function all(): array
    {
        return HouseholdShelterStatus::values();
    }

    public static function databaseMap(): array
    {
        return [
            1 => HouseholdShelterStatus::TENT,
            2 => HouseholdShelterStatus::MAKESHIFT_SHELTER,
            3 => HouseholdShelterStatus::TRANSITIONAL_SHELTER,
            4 => HouseholdShelterStatus::HOUSE_APARTMENT_SEVERELY_DAMAGED,
            5 => HouseholdShelterStatus::HOUSE_APARTMENT_MODERATELY_DAMAGED,
            6 => HouseholdShelterStatus::HOUSE_APARTMENT_NOT_DAMAGED,
            7 => HouseholdShelterStatus::ROOM_OR_SPACE_IN_PUBLIC_BUILDING,
            8 => HouseholdShelterStatus::ROOM_OR_SPACE_IN_UNFINISHED_BUILDING,
            9 => HouseholdShelterStatus::OTHER,
            10 => HouseholdShelterStatus::HOUSE_APARTMENT_LIGHTLY_DAMAGED,
        ];
    }
}
