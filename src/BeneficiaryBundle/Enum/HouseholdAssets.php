<?php

namespace BeneficiaryBundle\Enum;

use NewApiBundle\Enum\EnumKeyValueTransformationTrait;

class HouseholdAssets
{
    use EnumKeyValueTransformationTrait;

    const AC = 'A/C';
    const AGRICULTURAL_LAND = 'Agricultural Land';
    const CAR = 'Car';
    const FLATSCREEN_TV = 'Flatscreen TV';
    const LIVESTOCK = 'Livestock';
    const MOTORBIKE = 'Motorbike';
    const WASHING_MACHINE = 'Washing Machine';

    protected static $values = [
        0 => self::AC,
        1 => self::AGRICULTURAL_LAND,
        2 => self::CAR,
        3 => self::FLATSCREEN_TV,
        4 => self::LIVESTOCK,
        5 => self::MOTORBIKE,
        6 => self::WASHING_MACHINE,
    ];

}
