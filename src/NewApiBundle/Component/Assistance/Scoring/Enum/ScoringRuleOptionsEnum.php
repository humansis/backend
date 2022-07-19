<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring\Enum;

/**
 * List of supported values for each calculation rules
 */
final class ScoringRuleOptionsEnum
{
    public const VULNERABILITY_SOLO_PARENT = 'Solo parent';

    public const VULNERABILITY_PREGNANT_OR_LACTATING = 'Pregnant or lactating';

    public const DEPENDENCY_RATIO_MID = '1 (mid.)';
    public const DEPENDENCY_RATIO_HIGH = '>1 (hight dep.)';

    public const CHRONICALLY_ILL_ONE = 'one';
    public const CHRONICALLY_ILL_TWO_OR_MORE = 'two and more';

    public const CHRONICALLY_ILL = 'Chronically ill';
    public const AGE_18 = 'Age < 18';
    public const AGE_59 = 'Age > 59';
    public const PREGNANT_LACTATING_FEMALE = 'Pregnant or lactating female';
    public const DISABLED = 'Disabled';
    public const NO_VULNERABILITY = 'No vulnerability';

    public const VERY_LOW_VULNERABILITY = '0 > r <= 1';
    public const LOW_VULNERABILITY = 'r = 2';
    public const MODERATE_VULNERABILITY = 'r = 3';
    public const HIGH_VULNERABILITY = 'r = 4';
    public const VERY_HIGH_VULNERABILITY = 'r = 5';
    public const EXTREME_VULNERABILITY = 'r = 0 || r <=6';


    public const SUPPORTED = [
        ScoringRulesEnum::SINGLE_PARENT_HEADED => [
            self::VULNERABILITY_SOLO_PARENT,
        ],

        ScoringRulesEnum::PREGNANT_OR_LACTATING => [
            self::VULNERABILITY_PREGNANT_OR_LACTATING,
        ],

        ScoringRulesEnum::DEPENDENCY_RATIO_UKR => [
            self::DEPENDENCY_RATIO_MID,
            self::DEPENDENCY_RATIO_HIGH,
        ],

        ScoringRulesEnum::NO_OF_CHRONICALLY_ILL => [
            self::CHRONICALLY_ILL_ONE,
            self::CHRONICALLY_ILL_TWO_OR_MORE,
        ],

        ScoringRulesEnum::HH_HEAD_VULNERABILITY => [
            self::CHRONICALLY_ILL,
            self::AGE_18,
            self::AGE_59,
            self::PREGNANT_LACTATING_FEMALE,
            self::DISABLED,
            self::NO_VULNERABILITY,
        ],

        ScoringRulesEnum::HH_MEMBERS_VULNERABILITY => [
            self::CHRONICALLY_ILL,
            self::PREGNANT_LACTATING_FEMALE,
            self::DISABLED,
            self::NO_VULNERABILITY,
        ],

        ScoringRulesEnum::COMPLEX_DEPENDENCY_RATIO => [
            self::VERY_LOW_VULNERABILITY,
            self::LOW_VULNERABILITY,
            self::MODERATE_VULNERABILITY,
            self::HIGH_VULNERABILITY,
            self::VERY_HIGH_VULNERABILITY,
            self::EXTREME_VULNERABILITY,
        ],
    ];
}
