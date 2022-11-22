<?php

namespace Enum;

class ResidencyStatus
{
    use EnumTrait;

    public const REFUGEE = 'refugee';
    public const IDP = 'IDP';
    public const RESIDENT = 'resident';
    public const RETURNEE = 'returnee';

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
