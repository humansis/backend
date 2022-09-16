<?php
declare(strict_types=1);

namespace Enum;

final class AssistanceType
{
    const DISTRIBUTION = 'distribution';
    const ACTIVITY = 'activity';

    public static function values()
    {
        return [
            self::ACTIVITY,
            self::DISTRIBUTION,
        ];
    }
}
