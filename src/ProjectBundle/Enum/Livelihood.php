<?php
declare(strict_types=1);

namespace ProjectBundle\Enum;

use NewApiBundle\Enum\EnumTrait;

final class Livelihood
{
    use EnumTrait;

    const DAILY_LABOUR = 'Daily Labour';
    const FARMING_AGRICULTURE = 'Farming - Agriculture';
    const FARMING_LIVESTOCK = 'Farming - Livestock';
    const GOVERNMENT = 'Government';
    const HOME_DUTIES = 'Home Duties';
    const TRADING = 'Trading';
    const OWN_BUSINESS = 'Own Business';
    const TEXTILES = 'Textiles';

    public static function values()
    {
        return [
            self::DAILY_LABOUR,
            self::FARMING_AGRICULTURE,
            self::FARMING_LIVESTOCK,
            self::GOVERNMENT,
            self::HOME_DUTIES,
            self::TRADING,
            self::OWN_BUSINESS,
            self::TEXTILES,
        ];
    }

    public static function translate(string $livelihood): string
    {
        return $livelihood;
    }
}
