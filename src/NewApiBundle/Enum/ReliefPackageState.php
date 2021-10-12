<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class ReliefPackageState
{
    public const TO_DISTRIBUTE = 'To distribute';
    public const DISTRIBUTION_IN_PROGRESS = 'Distribution in progress';
    public const DISTRIBUTED = 'Distributed';
    public const EXPIRED = 'Expired';
    public const CANCELED = 'Canceled';

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
}
