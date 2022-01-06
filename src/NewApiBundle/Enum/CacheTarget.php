<?php declare(strict_types=1);

namespace NewApiBundle\Enum;

class CacheTarget
{
    public const ASSISTANCE = 'assistance';

    public static function assistanceId(int $id): string
    {
        return self::ASSISTANCE.'-'.$id;
    }
}
