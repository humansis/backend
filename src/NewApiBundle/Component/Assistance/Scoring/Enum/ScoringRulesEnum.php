<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring\Enum;

/**
 * List of supported calculation rules
 */
final class ScoringRulesEnum
{
    public const DEPENDENCY_RATIO_UKR = 'dependencyRatioUkr';
    public const SINGLE_PARENT_HEADED = 'singleParentHeaded';
    public const PREGNANT_OR_LACTATING = 'pregnantOrLactating';
    public const NO_OF_CHRONICALLY_ILL = 'noOfChronicallyIll';
    public const HH_HEAD_VULNERABILITY = 'hhHeadVulnerability';
    public const HH_MEMBERS_VULNERABILITY = 'hhMembersVulnerability';
    public const COMPLEX_DEPENDENCY_RATIO = 'complexDependencyRatio';

    public static function values(): array
    {
        return [
            self::DEPENDENCY_RATIO_UKR,
            self::SINGLE_PARENT_HEADED,
            self::PREGNANT_OR_LACTATING,
            self::NO_OF_CHRONICALLY_ILL,
            self::HH_HEAD_VULNERABILITY,
            self::HH_MEMBERS_VULNERABILITY,
            self::COMPLEX_DEPENDENCY_RATIO,
        ];
    }
}
