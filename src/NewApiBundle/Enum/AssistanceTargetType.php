<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class AssistanceTargetType
{
    const INDIVIDUAL = 'individual';
    const HOUSEHOLD = 'household';
    const COMMUNITY = 'community';
    const INSTITUTION = 'institution';

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
