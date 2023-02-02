<?php

declare(strict_types=1);

namespace Enum;

class CacheTarget
{
    final public const ASSISTANCE = 'assistance';

    final public const PROJECT = 'project';

    public static function assistanceId(int $id): string
    {
        return self::ASSISTANCE . '-' . $id;
    }

    public static function projectId(int $id): string
    {
        return self::PROJECT . '-' . $id;
    }

    public static function assistanceCountInProject(int $projectId): string
    {
        return self::projectId($projectId) . '-assistance-count';
    }
}
