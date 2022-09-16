<?php
declare(strict_types=1);

namespace Component\Assistance\Scoring\Enum;

final class ScoringCsvColumns
{
    public const RULE_TYPE = 'Rule type';
    public const FIELD_NAME = 'Field Name';
    public const TITLE = 'Title';
    public const OPTIONS = 'Options';
    public const POINTS = 'Points';

    public static function values(): array
    {
        return [
            self::RULE_TYPE,
            self::FIELD_NAME,
            self::TITLE,
            self::OPTIONS,
            self::POINTS,
        ];
    }
}
