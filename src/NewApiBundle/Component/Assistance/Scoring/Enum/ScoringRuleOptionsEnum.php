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

    public const SUPPORTED = [
        ScoringRulesEnum::SINGLE_PARENT_HEADED => [
            self::VULNERABILITY_SOLO_PARENT,
        ],

        ScoringRulesEnum::PREGNANT_OR_LACTATING => [
            self::VULNERABILITY_PREGNANT_OR_LACTATING,
        ],

        ScoringRulesEnum::DEPENDENCY_RATIO => [
            self::DEPENDENCY_RATIO_MID,
            self::DEPENDENCY_RATIO_HIGH,
        ],

        ScoringRulesEnum::NO_OF_CHRONICALLY_ILL => [
            self::CHRONICALLY_ILL_ONE,
            self::CHRONICALLY_ILL_TWO_OR_MORE,
        ]
    ];
}
