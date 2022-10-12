<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring\Enum;

/**
 * List of supported values for each calculation rules
 */
final class ScoringRuleCalculationOptionsEnum
{
    public const VULNERABILITY_SOLO_PARENT = 'Solo parent';

    public const VULNERABILITY_PREGNANT_OR_LACTATING = 'Pregnant or lactating';

    public const DEPENDENCY_RATIO_MID = '1 (mid.)';
    public const DEPENDENCY_RATIO_HIGH = '>1 (hight dep.)';

    public const CHRONICALLY_ILL_ONE = 'one';
    public const CHRONICALLY_ILL_TWO_OR_MORE = 'two and more';

    public const GENDER_MALE = 'Male';
    public const GENDER_FEMALE = 'Female';

    public const CHRONICALLY_ILL_OR_DISABLED = 'Chronically ill or Person with disability';
    public const INFANT = 'Infant';
    public const ELDERLY = 'Elderly';

    public const DEPENDENCY_RATIO_SYR_ZERO_DIVISION = 'division by zero';
    public const DEPENDENCY_RATIO_SYR_LOW = '<=1.5';
    public const DEPENDENCY_RATIO_SYR_HIGH = '>1.5';

    public const SUPPORTED = [
        ScoringRulesCalculationsEnum::SINGLE_PARENT_HEADED => [
            self::VULNERABILITY_SOLO_PARENT,
        ],

        ScoringRulesCalculationsEnum::PREGNANT_OR_LACTATING => [
            self::VULNERABILITY_PREGNANT_OR_LACTATING,
        ],

        ScoringRulesCalculationsEnum::DEPENDENCY_RATIO_UKR => [
            self::DEPENDENCY_RATIO_MID,
            self::DEPENDENCY_RATIO_HIGH,
        ],

        ScoringRulesCalculationsEnum::NO_OF_CHRONICALLY_ILL => [
            self::CHRONICALLY_ILL_ONE,
            self::CHRONICALLY_ILL_TWO_OR_MORE,
        ],

        ScoringRulesCalculationsEnum::GENDER_OF_HEAD_OF_HOUSEHOLD => [
            self::GENDER_FEMALE,
            self::GENDER_MALE,
        ],

        ScoringRulesCalculationsEnum::VULNERABILITY_HEAD_OF_HOUSEHOLD => [
            self::CHRONICALLY_ILL_OR_DISABLED,
            self::INFANT,
            self::ELDERLY,
        ],

        ScoringRulesCalculationsEnum::DEPENDENCY_RATIO_SYR => [
            self::DEPENDENCY_RATIO_SYR_ZERO_DIVISION,
            self::DEPENDENCY_RATIO_SYR_LOW,
            self::DEPENDENCY_RATIO_SYR_HIGH,
        ],
    ];
}
