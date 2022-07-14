<?php

namespace NewApiBundle\Enum;

class ResidencyStatus
{
    use EnumTrait;

    const REFUGEE = 'refugee';
    const IDP = 'IDP';
    const RESIDENT = 'resident';
    const RETURNEE = 'returnee';

    protected static $values = [
        self::REFUGEE,
        self::IDP,
        self::RESIDENT,
        self::RETURNEE,
    ];

    /**
     * @deprecated use ResidencyStatus::values instead
     * @return string[]
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
