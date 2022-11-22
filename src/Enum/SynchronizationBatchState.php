<?php

declare(strict_types=1);

namespace Enum;

final class SynchronizationBatchState
{
    use EnumTrait;

    public const UPLOADED = 'Uploaded';
    public const CORRECT = 'Correct';
    public const INCORRECT = 'Errors';
    public const ARCHIVED = 'Archived';

    public static function values(): array
    {
        return [
            self::UPLOADED,
            self::CORRECT,
            self::INCORRECT,
            self::ARCHIVED,
        ];
    }
}
