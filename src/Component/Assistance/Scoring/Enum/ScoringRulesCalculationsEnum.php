<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring\Enum;

/**
 * List of supported calculation rules
 */
final class ScoringRulesCalculationsEnum
{
    public const DEPENDENCY_RATIO_UKR = 'dependencyRatioUkr';
    public const SINGLE_PARENT_HEADED = 'singleParentHeaded';
    public const PREGNANT_OR_LACTATING = 'pregnantOrLactating';
    public const NO_OF_CHRONICALLY_ILL = 'noOfChronicallyIll';
    public const GENDER_OF_HEAD_OF_HOUSEHOLD = 'genderOfHeadOfHousehold';
    public const VULNERABILITY_HEAD_OF_HOUSEHOLD_NWS = 'vulnerabilityHeadOfHouseholdNWS';
    public const VULNERABILITY_HEAD_OF_HOUSEHOLD_NES = 'vulnerabilityHeadOfHouseholdNES';
    public const DEPENDENCY_RATIO_SYR_NWS = 'dependencyRatioSyrNWS';
    public const DEPENDENCY_RATIO_SYR_NES = 'dependencyRatioSyrNES';
    public const INCOME_SPENT_ON_FOOD = 'incomeSpentOnFood';

    public static function values(): array
    {
        return [
            self::DEPENDENCY_RATIO_UKR,
            self::SINGLE_PARENT_HEADED,
            self::PREGNANT_OR_LACTATING,
            self::NO_OF_CHRONICALLY_ILL,
            self::GENDER_OF_HEAD_OF_HOUSEHOLD,
            self::VULNERABILITY_HEAD_OF_HOUSEHOLD_NWS,
            self::VULNERABILITY_HEAD_OF_HOUSEHOLD_NES,
            self::DEPENDENCY_RATIO_SYR_NWS,
            self::DEPENDENCY_RATIO_SYR_NES,
            self::INCOME_SPENT_ON_FOOD,
        ];
    }
}
