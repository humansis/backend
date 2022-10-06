<?php

declare(strict_types=1);

namespace Workflow;

final class SynchronizationBatchTransitions
{
    public const COMPLETE_VALIDATION = 'Complete validation';
    public const FAIL_VALIDATION = 'Fail validation';
    public const ARCHIVE = 'Archive';

    public static function values(): array
    {
        return [
            self::COMPLETE_VALIDATION,
            self::FAIL_VALIDATION,
            self::ARCHIVE,
        ];
    }
}
