<?php

declare(strict_types=1);

namespace Enum;

final class SynchronizationBatchValidationType
{
    use EnumTrait;

    public const DEPOSIT = 'Deposit';
    public const PURCHASE = 'Purchase';

    public static function values(): array
    {
        return [
            self::DEPOSIT,
            self::PURCHASE,
        ];
    }
}
