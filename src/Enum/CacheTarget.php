<?php

declare(strict_types=1);

namespace Enum;

class CacheTarget
{
    final public const ASSISTANCE = 'assistance';

    public static function assistanceId(int $id): string
    {
        return self::ASSISTANCE . '-' . $id;
    }
}
