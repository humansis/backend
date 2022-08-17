<?php
declare(strict_types=1);

namespace Workflow;

final class SynchronizationBatchTransitions
{
    const COMPLETE_VALIDATION = 'Complete validation';
    const FAIL_VALIDATION = 'Fail validation';
    const ARCHIVE = 'Archive';

    public static function values(): array
    {
        return [
            self::COMPLETE_VALIDATION,
            self::FAIL_VALIDATION,
            self::ARCHIVE,
        ];
    }
}
