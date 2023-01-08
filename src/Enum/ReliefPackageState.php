<?php

declare(strict_types=1);

namespace Enum;

use Workflow\ReliefPackageTransitions;

final class ReliefPackageState
{
    use EnumTrait;

    public const TO_DISTRIBUTE = 'To distribute';
    public const DISTRIBUTION_IN_PROGRESS = 'Distribution in progress';
    public const DISTRIBUTED = 'Distributed';
    public const EXPIRED = 'Expired';
    public const CANCELED = 'Canceled';

    const RELIEF_PACKAGE_STATES = [
        self::TO_DISTRIBUTE,
        self::DISTRIBUTION_IN_PROGRESS,
        self::DISTRIBUTED,
        self::EXPIRED,
        self::CANCELED,
    ];

    public static function transitionsMapper(): array
    {
        return [
            self::TO_DISTRIBUTE => ReliefPackageTransitions::REUSE,
            self::DISTRIBUTED => ReliefPackageTransitions::DISTRIBUTE,
            self::EXPIRED => ReliefPackageTransitions::EXPIRE,
            self::CANCELED => ReliefPackageTransitions::CANCEL,
        ];
    }

    public static function values(): array
    {
        return self::RELIEF_PACKAGE_STATES;
    }

    public static function distributableStates(): array
    {
        return [
            self::TO_DISTRIBUTE,
            self::CANCELED,
        ];
    }

    public static function distributionStartedStates(): array
    {
        return [
            self::DISTRIBUTION_IN_PROGRESS,
            self::DISTRIBUTED,
        ];
    }
}
