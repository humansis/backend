<?php

namespace DBAL;

use Enum\Livelihood;

class LivelihoodEnum extends AbstractEnum
{
    use EnumTrait;

    public function getName(): string
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
            'irregular_earnings' => Livelihood::IRREGULAR_EARNINGS,
            'farming_agriculture' => Livelihood::FARMING_AGRICULTURE,
            'farming_livestock' => Livelihood::FARMING_LIVESTOCK,
            'regular_salary_private' => Livelihood::REGULAR_SALARY_PRIVATE,
            'regular_salary_public' => Livelihood::REGULAR_SALARY_PUBLIC,
            'social_welfare' => Livelihood::SOCIAL_WELFARE,
            'pension' => Livelihood::PENSION,
            'home_duties' => Livelihood::HOME_DUTIES,
            'own_business_trading' => Livelihood::OWN_BUSINESS_TRADING,
            'savings' => Livelihood::SAVINGS,
            'remittances' => Livelihood::REMITTANCES,
            'humanitarian_aid' => Livelihood::HUMANITARIAN_AID,
            'no_income' => Livelihood::NO_INCOME,
            'refused_to_answer' => Livelihood::REFUSED_TO_ANSWER,
            'other' => Livelihood::OTHER,
        ];
    }
}
