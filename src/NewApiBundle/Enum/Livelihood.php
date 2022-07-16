<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class Livelihood
{
    use EnumTrait;

    const IRREGULAR_EARNINGS = 'Irregular earnings'; //DAILY_LABOUR
    const FARMING_AGRICULTURE = 'Farming - Agriculture';
    const FARMING_LIVESTOCK = 'Farming - Livestock';
    const REGULAR_SALARY_PRIVATE = 'Regular salary - private sector'; //new
    const REGULAR_SALARY_PUBLIC = 'Regular salary - public sector'; //GOVERNMENT
    const SOCIAL_WELFARE = 'Social welfare'; //new
    const PENSION = 'Pension'; //new
    const HOME_DUTIES = 'Home Duties';
    const OWN_BUSINESS_TRADING = 'Own business/trading'; //TRADING + OWN_BUSINESS
    const SAVINGS = 'Savings';
    const REMITTANCES = 'Remittances';
    const HUMANITARIAN_AID = 'Humanitarian aid';
    const NO_INCOME = 'No Income';
    const REFUSED_TO_ANSWER = 'Refused to answer';
    const OTHER = 'Other';




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
