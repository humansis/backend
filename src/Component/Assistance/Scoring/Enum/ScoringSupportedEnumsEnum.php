<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring\Enum;

final class ScoringSupportedEnumsEnum
{
    final public const HOUSEHOLD_SHELTER_STATUS = 'HouseholdShelterStatus';

    public static function values(): array
    {
        return [
            self::HOUSEHOLD_SHELTER_STATUS,
        ];
    }
}
