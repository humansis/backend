<?php
declare(strict_types=1);

namespace DistributionBundle\Enum;

final class AssistanceCommodity
{
    const QR_VOUCHER = 'QR Code Voucher';
    const MOBILE_MONEY = 'Mobile Money';

    public static function values()
    {
        return [
            self::QR_VOUCHER,
            self::MOBILE_MONEY,
        ];
    }
}
