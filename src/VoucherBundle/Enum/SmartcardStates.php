<?php
declare(strict_types=1);

namespace VoucherBundle\Enum;

class SmartcardStates
{
    const UNASSIGNED = 'unassigned';
    const ACTIVE = 'active';
    const INACTIVE = 'inactive';
    const CANCELLED = 'cancelled';

    protected static $values = [
        self::UNASSIGNED,
        self::ACTIVE,
        self::INACTIVE,
        self::CANCELLED,
    ];

    public static function all()
    {
        return self::$values;
    }
}
