<?php

declare(strict_types=1);

namespace Enum;

final class AssistanceType
{
    public const DISTRIBUTION = 'distribution';
    public const ACTIVITY = 'activity';

    public static function values()
    {
        return [
            self::ACTIVITY,
            self::DISTRIBUTION,
        ];
    }
}
