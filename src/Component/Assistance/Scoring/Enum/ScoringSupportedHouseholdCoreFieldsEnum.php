<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring\Enum;

final class ScoringSupportedHouseholdCoreFieldsEnum
{
    public const NOTES = 'notes';
    public const INCOME = 'income';
    public const FOOD_CONSUMPTION_SCORE = 'foodConsumptionScore';
    public const COPING_STRATEGIES_INDEX = 'copingStrategiesIndex';
    public const DEBT_LEVEL = 'debtLevel';
    public const INCOME_SPENT_ON_FOOD = 'incomeSpentOnFood';
    public const HOUSEHOLD_INCOME = 'householdIncome';

    public const ASSETS = 'assets';
    public const SUPPORT_RECEIVED_TYPES = 'supportReceivedTypes';

    public static function values(): array
    {
        return [
            self::NOTES,
            self::INCOME,
            self::FOOD_CONSUMPTION_SCORE,
            self::COPING_STRATEGIES_INDEX,
            self::DEBT_LEVEL,
            self::INCOME_SPENT_ON_FOOD,
            self::HOUSEHOLD_INCOME,
            self::ASSETS,
            self::SUPPORT_RECEIVED_TYPES,
        ];
    }
}
