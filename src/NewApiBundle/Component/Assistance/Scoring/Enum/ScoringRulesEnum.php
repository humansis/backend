<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring\Enum;

/**
 * List of supported calculation rules
 */
final class ScoringRulesEnum
{
    public const DEPENDENCY_RATIO = 'dependencyRatio';
    public const SINGLE_PARENT_HEADED = 'singleParentHeaded';
    public const PREGNANT_OR_LACTATING = 'pregnantOrLactating';

    public static function values(): array
    {
        return [
            self::DEPENDENCY_RATIO,
            self::SINGLE_PARENT_HEADED,
            self::PREGNANT_OR_LACTATING,
        ];
    }
}
