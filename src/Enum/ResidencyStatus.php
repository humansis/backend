<?php

namespace Enum;

class ResidencyStatus
{
    use EnumTrait;

    final public const REFUGEE = 'refugee';
    final public const IDP = 'IDP';
    final public const RESIDENT = 'resident';
    final public const RETURNEE = 'returnee';

    protected static $values = [
        self::REFUGEE,
        self::IDP,
        self::RESIDENT,
        self::RETURNEE,
    ];

    /**
     * @return string[]
     * @deprecated use ResidencyStatus::values instead
     */
    public static function all()
    {
        return self::$values;
    }

    public static function values(): array
    {
        return self::$values;
    }
}
