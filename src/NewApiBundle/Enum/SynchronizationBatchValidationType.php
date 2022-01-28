<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class SynchronizationBatchValidationType
{
    use EnumTrait;

    const DEPOSIT = 'Deposit';
    const PURCHASE = 'Purchase';

    public static function values(): array
    {
        return [
            self::DEPOSIT,
            self::PURCHASE,
        ];
    }
}
