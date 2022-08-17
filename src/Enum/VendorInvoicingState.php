<?php declare(strict_types=1);

namespace Enum;

final class VendorInvoicingState
{
    use EnumTrait;

    public const
        TO_REDEEM = 'toRedeem',
        SYNC_REQUIRED = 'syncRequired',
        INVOICED = 'invoiced';

    public static function values(): array
    {
        return [
            self::TO_REDEEM,
            self::SYNC_REQUIRED,
            self::INVOICED,
        ];
    }

    public static function notCompletedValues(): array
    {
        return [
            self::TO_REDEEM,
            self::SYNC_REQUIRED,
        ];
    }
}
