<?php

namespace BeneficiaryBundle\Enum;

class ResidencyStatus
{
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

    public static function all()
    {
        return self::$values;
    }
}
