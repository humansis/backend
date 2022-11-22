<?php

declare(strict_types=1);

namespace DBAL;

use Enum\HouseholdAssets;

class HouseholdAssetsEnum extends AbstractEnum
{
    use EnumTrait;

    // unused yet, prepared for future migration
    public function getName(): string
    {
        return 'enum_household_assets';
    }

    public static function all(): array
    {
        return array_keys(self::databaseMap());
    }

    public static function databaseMap(): array
    {
        return [
            0 => HouseholdAssets::AC,
            1 => HouseholdAssets::AGRICULTURAL_LAND,
            2 => HouseholdAssets::CAR,
            3 => HouseholdAssets::FLATSCREEN_TV,
            4 => HouseholdAssets::LIVESTOCK,
            5 => HouseholdAssets::MOTORBIKE,
            6 => HouseholdAssets::WASHING_MACHINE,
        ];
    }
}
