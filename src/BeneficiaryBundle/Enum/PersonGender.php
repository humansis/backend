<?php

namespace BeneficiaryBundle\Enum;

use NewApiBundle\Enum\EnumKeyValueTransformationTrait;

class PersonGender
{
    use EnumKeyValueTransformationTrait;

    const MALE = 'Male';
    const FEMALE = 'Female';

    protected static $values = [
        0 => self::FEMALE,
        1 => self::MALE,
    ];

    protected static $alternatives = [
        self::FEMALE => ['F'],
        self::MALE => ['M'],
    ];

}
