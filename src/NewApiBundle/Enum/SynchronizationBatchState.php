<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class SynchronizationBatchState
{
    use EnumTrait;

    const UPLOADED = 'Uploaded';
    const CORRECT = 'Correct';
    const INCORRECT = 'Errors';
    const ARCHIVED = 'Archived';

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
