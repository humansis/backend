<?php

namespace ProjectBundle\DBAL;

use CommonBundle\DBAL\AbstractEnum;
use NewApiBundle\DBAL\EnumTrait;
use ProjectBundle\Enum\Livelihood;

class LivelihoodEnum extends AbstractEnum
{
    use EnumTrait;

    public function getName()
    {
        return 'enum_livelihood';
    }

    public static function all(): array
    {
        return array_keys(self::databaseMap());
    }

    public static function databaseMap(): array
    {
        return [
            'daily_labour' => Livelihood::DAILY_LABOUR,
            'farming_agriculture' => Livelihood::FARMING_AGRICULTURE,
            'farming_livestock' => Livelihood::FARMING_LIVESTOCK,
            'government' => Livelihood::GOVERNMENT,
            'home_duties' => Livelihood::HOME_DUTIES,
            'trading' => Livelihood::TRADING,
            'own_business' => Livelihood::OWN_BUSINESS,
            'textiles' => Livelihood::TEXTILES,
        ];
    }
}
