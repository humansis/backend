<?php

declare(strict_types=1);

namespace Enum;

class SmartcardStates
{
    use EnumTrait;

    public const UNASSIGNED = 'unassigned';
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const REUSED = 'reused';
    public const CANCELLED = 'cancelled';

    protected static $values = [
        self::UNASSIGNED,
        self::ACTIVE,
        self::INACTIVE,
        self::REUSED,
        self::CANCELLED,
    ];

    protected static $possibleFlow = [
        SmartcardStates::UNASSIGNED => [SmartcardStates::ACTIVE],
        SmartcardStates::ACTIVE => [SmartcardStates::INACTIVE, SmartcardStates::CANCELLED],
        SmartcardStates::INACTIVE => [SmartcardStates::ACTIVE, SmartcardStates::CANCELLED],
        SmartcardStates::REUSED => [],
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

    public static function values(): array
    {
        return self::all();
    }
}
