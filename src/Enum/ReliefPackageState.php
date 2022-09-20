<?php

declare(strict_types=1);

namespace Enum;

final class ReliefPackageState
{
    use EnumTrait;

    public const TO_DISTRIBUTE = 'To distribute';
    public const DISTRIBUTION_IN_PROGRESS = 'Distribution in progress';
    public const DISTRIBUTED = 'Distributed';
    public const EXPIRED = 'Expired';
    public const CANCELED = 'Canceled';

    protected static $possibleFlow = [
        ReliefPackageState::TO_DISTRIBUTE => [ReliefPackageState::CANCELED],
        ReliefPackageState::DISTRIBUTION_IN_PROGRESS => [],
        ReliefPackageState::DISTRIBUTED => [],
        ReliefPackageState::EXPIRED => [ReliefPackageState::TO_DISTRIBUTE,ReliefPackageState::DISTRIBUTED],
        ReliefPackageState::CANCELED => []
    ];

    public static function isTransitionAllowed(string $stateFrom, string $stateTo): bool
    {
        return in_array($stateTo, self::$possibleFlow[$stateFrom]);
    }


    public static function values(): array
    {
        return [
            self::TO_DISTRIBUTE,
            self::DISTRIBUTION_IN_PROGRESS,
            self::DISTRIBUTED,
            self::EXPIRED,
            self::CANCELED,
        ];
    }

    public static function startupValues(): array
    {
        return [
            self::TO_DISTRIBUTE,
        ];
    }
}
