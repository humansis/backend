<?php
declare(strict_types=1);

namespace NewApiBundle\Workflow;

final class SynchronizationBatchTransitions
{
    const MARK_CORRECT = 'Mark as correct';
    const MARK_INCORRECT = 'Mark as incorrect';
    const ARCHIVE = 'Archive';

    public static function values(): array
    {
        return [
            self::MARK_CORRECT,
            self::MARK_INCORRECT,
            self::ARCHIVE,
        ];
    }
}
