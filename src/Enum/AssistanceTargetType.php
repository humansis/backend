<?php

declare(strict_types=1);

namespace Enum;

final class AssistanceTargetType
{
    public const INDIVIDUAL = 'individual';
    public const HOUSEHOLD = 'household';
    public const COMMUNITY = 'community';
    public const INSTITUTION = 'institution';

    public static function values()
    {
        return [
            self::INDIVIDUAL,
            self::HOUSEHOLD,
            self::COMMUNITY,
            self::INSTITUTION,
        ];
    }
}
