<?php

declare(strict_types=1);

namespace Enum;

final class AssistanceState
{
    public const NEW = 'new';
    public const VALIDATED = 'validated';
    public const CLOSED = 'closed';

    public static function values(): array
    {
        return [
            self::NEW,
            self::VALIDATED,
            self::CLOSED,
        ];
    }
}
