<?php

namespace NewApiBundle\Component\Assistance\Scoring\Enum;

class ScoringSupportedEnumsEnum
{
    public const HOUSEHOLD_SHELTER_STATUS = 'HouseholdShelterStatus';

    public static function values(): array
    {
        return [
            self::HOUSEHOLD_SHELTER_STATUS,
        ];
    }
}