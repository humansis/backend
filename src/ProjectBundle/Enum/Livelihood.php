<?php
declare(strict_types=1);

namespace ProjectBundle\Enum;

final class Livelihood
{
    const DAILY_LABOUR = 'daily_labour';
    const FARMING_AGRICULTURE = 'farming_agriculture';
    const FARMING_LIVESTOCK = 'farming_livestock';
    const GOVERNMENT = 'government';
    const HOME_DUTIES = 'home_duties';
    const TRADING = 'trading';
    const OWN_BUSINESS = 'own_business';
    const TEXTILES = 'textiles';

    public const TRANSLATIONS = [
        self::DAILY_LABOUR => 'Daily Labour',
        self::FARMING_AGRICULTURE => 'Farming - Agriculture',
        self::FARMING_LIVESTOCK => 'Farming - Livestock',
        self::GOVERNMENT => 'Government',
        self::HOME_DUTIES => 'Home Duties',
        self::TRADING => 'Trading',
        self::OWN_BUSINESS => 'Own Business',
        self::TEXTILES => 'Textiles',
    ];

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
        if (!array_key_exists($livelihood, self::TRANSLATIONS)) {
            throw new \InvalidArgumentException("$livelihood is not valid Livelihood value.");
        }

        return self::TRANSLATIONS[$livelihood];
    }
}
