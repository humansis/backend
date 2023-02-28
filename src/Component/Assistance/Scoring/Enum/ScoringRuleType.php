<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring\Enum;

final class ScoringRuleType
{
    public const COUNTRY_SPECIFIC = 'countrySpecific';
    public const CALCULATION = 'calculation';
    public const ENUM = 'enum';
    public const CORE_HOUSEHOLD = 'coreHousehold';
    public const COMPUTED_VALUE = 'computedValue';

    public static function values(): array
    {
        return [
            self::COUNTRY_SPECIFIC,
            self::CALCULATION,
            self::ENUM,
            self::CORE_HOUSEHOLD,
            self::COMPUTED_VALUE,
        ];
    }
}
