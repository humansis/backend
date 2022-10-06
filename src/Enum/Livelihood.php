<?php

declare(strict_types=1);

namespace Enum;

final class Livelihood
{
    use EnumTrait;

    public const IRREGULAR_EARNINGS = 'Irregular earnings'; //DAILY_LABOUR
    public const FARMING_AGRICULTURE = 'Farming - Agriculture';
    public const FARMING_LIVESTOCK = 'Farming - Livestock';
    public const REGULAR_SALARY_PRIVATE = 'Regular salary - private sector'; //new
    public const REGULAR_SALARY_PUBLIC = 'Regular salary - public sector'; //GOVERNMENT
    public const SOCIAL_WELFARE = 'Social welfare'; //new
    public const PENSION = 'Pension'; //new
    public const HOME_DUTIES = 'Home Duties';
    public const OWN_BUSINESS_TRADING = 'Own business/trading'; //TRADING + OWN_BUSINESS
    public const SAVINGS = 'Savings';
    public const REMITTANCES = 'Remittances';
    public const HUMANITARIAN_AID = 'Humanitarian aid';
    public const NO_INCOME = 'No Income';
    public const REFUSED_TO_ANSWER = 'Refused to answer';
    public const OTHER = 'Other';

    public static function values()
    {
        return [
            self::IRREGULAR_EARNINGS,
            self::FARMING_AGRICULTURE,
            self::FARMING_LIVESTOCK,
            self::REGULAR_SALARY_PRIVATE,
            self::REGULAR_SALARY_PUBLIC,
            self::SOCIAL_WELFARE,
            self::PENSION,
            self::HOME_DUTIES,
            self::OWN_BUSINESS_TRADING,
            self::SAVINGS,
            self::REMITTANCES,
            self::HUMANITARIAN_AID,
            self::NO_INCOME,
            self::REFUSED_TO_ANSWER,
            self::OTHER,
        ];
    }

    public static function translate(string $livelihood): string
    {
        return $livelihood;
    }
}
