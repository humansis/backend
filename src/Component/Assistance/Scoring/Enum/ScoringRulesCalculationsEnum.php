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
    public const VULNERABILITY_HEAD_OF_HOUSEHOLD = 'vulnerabilityHeadOfHousehold';
    public const DEPENDENCY_RATIO_SYR = 'dependencyRatioSyr';

    public static function values(): array
    {
        return [
            self::DEPENDENCY_RATIO_UKR,
            self::SINGLE_PARENT_HEADED,
            self::PREGNANT_OR_LACTATING,
            self::NO_OF_CHRONICALLY_ILL,
            self::GENDER_OF_HEAD_OF_HOUSEHOLD,
            self::VULNERABILITY_HEAD_OF_HOUSEHOLD,
            self::DEPENDENCY_RATIO_SYR,
        ];
    }
}
