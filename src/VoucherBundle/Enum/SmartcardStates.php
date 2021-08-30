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

    protected static $possibleFlow = [
        SmartcardStates::UNASSIGNED => [SmartcardStates::ACTIVE],
        SmartcardStates::ACTIVE => [SmartcardStates::INACTIVE, SmartcardStates::CANCELLED],
        SmartcardStates::INACTIVE => [SmartcardStates::CANCELLED],
        SmartcardStates::CANCELLED => [],
    ];

    public static function all()
    {
        return self::$values;
    }

    public static function isTransitionAllowed(string $stateFrom, string $stateTo): bool
    {
        return in_array($stateTo, self::$possibleFlow[$stateFrom]);
    }
}
