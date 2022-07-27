<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring\Enum;

/**
 * List of supported calculation rules
 */
final class ScoringRulesCalculationsEnum
{
    public const DEPENDENCY_RATIO_UKR = 'dependencyRatioUkr';
    public const COMPLEX_DEPENDENCY_RATIO = 'complexDependencyRatio';
    public const SINGLE_PARENT_HEADED = 'singleParentHeaded';
    public const PREGNANT_OR_LACTATING = 'pregnantOrLactating';
    public const NO_OF_CHRONICALLY_ILL = 'noOfChronicallyIll';
    public const HH_HEAD_VULNERABILITY = 'hhHeadVulnerability';
    public const HH_MEMBERS_VULNERABILITY = 'hhMembersVulnerability';
    public const ASSETS = 'productiveAssets';
    public const CSI = 'csi';
    public const INCOME_SPENT_ON_FOOD = 'incomeSpentOnFood';
    public const FCS = 'fcs';
    public const HH_HEAD_GENDER = 'hhHeadGender';
    public const DEBT = 'debt';
    public const ASSISTANCE_PROVIDED = 'assistanceProvided';

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
            self::ASSETS,
            self::CSI,
            self::INCOME_SPENT_ON_FOOD,
            self::FCS,
            self::HH_HEAD_GENDER,
            self::DEBT,
            self::ASSISTANCE_PROVIDED,
        ];
    }
}
