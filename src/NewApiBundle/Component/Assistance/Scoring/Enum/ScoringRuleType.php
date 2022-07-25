<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring\Enum;

final class ScoringRuleType
{
    public const COUNTRY_SPECIFIC = 'countrySpecific';
    public const CALCULATION = 'calculation';
    public const ENUM = 'enum';

    public static function values(): array
    {
        return [
            self::COUNTRY_SPECIFIC,
            self::CALCULATION,
            self::ENUM,
        ];
    }
}
