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

    public const CHRONICALLY_ILL = 'Chronically ill';
    public const PERSON_WITH_DISABILITY = 'Person with disability';
    public const PREGNANT_OR_LACTATING_FEMALE = 'Pregnant or lactating female';

    public const DEPENDENCY_RATIO_SYR_ZERO_DIVISION = 'division by zero';
    public const DEPENDENCY_RATIO_SYR_NWS_LOW = '<=1.5';
    public const DEPENDENCY_RATIO_SYR_NWS_HIGH = '>1.5';

    public const DEPENDENCY_RATIO_SYR_NES_0 = '0';
    public const DEPENDENCY_RATIO_SYR_NES_1 = '0< DR <= 1';
    public const DEPENDENCY_RATIO_SYR_NES_2 = '1< DR <= 2';
    public const DEPENDENCY_RATIO_SYR_NES_3 = '2< DR <= 3';
    public const DEPENDENCY_RATIO_SYR_NES_4 = '3< DR <= 4';
    public const DEPENDENCY_RATIO_SYR_NES_5 = '4< DR <= 5';
    public const DEPENDENCY_RATIO_SYR_NES_INF = '5 < DR';

    public const INCOME_SPENT_ON_FOOD_0 = '0';
    public const INCOME_SPENT_ON_FOOD_INCOME_0 = 'Income (core) = 0';
    public const INCOME_SPENT_ON_FOOD_25 = '<0.25';
    public const INCOME_SPENT_ON_FOOD_50 = '0,26-0,50';
    public const INCOME_SPENT_ON_FOOD_65 = '0,51-0,65';
    public const INCOME_SPENT_ON_FOOD_80 = '0,66-0,80';
    public const INCOME_SPENT_ON_FOOD_95 = '0,81-0,95';
    public const INCOME_SPENT_ON_FOOD_INF = '>0,95';

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

        ScoringRulesCalculationsEnum::VULNERABILITY_HEAD_OF_HOUSEHOLD_NWS => [
            self::CHRONICALLY_ILL_OR_DISABLED,
            self::INFANT,
            self::ELDERLY,
        ],

        ScoringRulesCalculationsEnum::VULNERABILITY_HEAD_OF_HOUSEHOLD_NES => [
            self::CHRONICALLY_ILL,
            self::PERSON_WITH_DISABILITY,
            self::INFANT,
            self::ELDERLY,
            self::PREGNANT_OR_LACTATING_FEMALE,
        ],

        ScoringRulesCalculationsEnum::DEPENDENCY_RATIO_SYR_NWS => [
            self::DEPENDENCY_RATIO_SYR_ZERO_DIVISION,
            self::DEPENDENCY_RATIO_SYR_NWS_LOW,
            self::DEPENDENCY_RATIO_SYR_NWS_HIGH,
        ],

        ScoringRulesCalculationsEnum::DEPENDENCY_RATIO_SYR_NES => [
            self::DEPENDENCY_RATIO_SYR_ZERO_DIVISION,
            self::DEPENDENCY_RATIO_SYR_NES_0,
            self::DEPENDENCY_RATIO_SYR_NES_1,
            self::DEPENDENCY_RATIO_SYR_NES_2,
            self::DEPENDENCY_RATIO_SYR_NES_3,
            self::DEPENDENCY_RATIO_SYR_NES_4,
            self::DEPENDENCY_RATIO_SYR_NES_5,
            self::DEPENDENCY_RATIO_SYR_NES_INF,
        ],

        ScoringRulesCalculationsEnum::INCOME_SPENT_ON_FOOD => [
            self::INCOME_SPENT_ON_FOOD_0,
            self::INCOME_SPENT_ON_FOOD_INCOME_0,
            self::INCOME_SPENT_ON_FOOD_25,
            self::INCOME_SPENT_ON_FOOD_50,
            self::INCOME_SPENT_ON_FOOD_65,
            self::INCOME_SPENT_ON_FOOD_80,
            self::INCOME_SPENT_ON_FOOD_95,
            self::INCOME_SPENT_ON_FOOD_INF,
        ],
    ];
}
