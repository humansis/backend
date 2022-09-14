<?php declare(strict_types=1);

namespace NewApiBundle\Enum;

final class SelectionCriteriaField
{
    use EnumTrait;

    public const
        GENDER = 'gender',
        DATE_OF_BIRTH = 'dateOfBirth',
        RESIDENCY_STATUS = 'residencyStatus',
        HAS_NOT_BEEN_IN_DISTRIBUTIONS_SINCE = 'hasNotBeenInDistributionsSince',
        DISABLED_HEAD_OF_HOUSEHOLD = 'disabledHeadOfHousehold',
        HAS_VALID_SMARTCARD = 'hasValidSmartcard',
        HEAD_OF_HOUSEHOLD_DATE_OF_BIRTH = 'headOfHouseholdDateOfBirth',
        HEAD_OF_HOUSEHOLD_GENDER = 'headOfHouseholdGender',
        LIVELIHOD = 'livelihood',
        FOOD_CONSUMPTION_SCORE = 'foodConsumptionScore',
        COPING_STRATEGIES_INDEX = 'copingStrategiesIndex',
        INCOME_LEVEL = 'incomeLevel',
        HOUSEHOLD_SIZE = 'householdSize',
        CURRENT_LOCATION = 'location',
        LOCATION_TYPE = 'locationType',
        CAMP_NAME = 'campName',
        VULNERABILITY_CRITERIA = 'vulnerabilityCriteria',
        COUNTRY_SPECIFIC = 'countrySpecific';

    public static function values(): array
    {
        return [
            self::GENDER,
            self::DATE_OF_BIRTH,
            self::RESIDENCY_STATUS,
            self::HAS_NOT_BEEN_IN_DISTRIBUTIONS_SINCE,
            self::DISABLED_HEAD_OF_HOUSEHOLD,
            self::HAS_VALID_SMARTCARD,
            self::HEAD_OF_HOUSEHOLD_DATE_OF_BIRTH,
            self::HEAD_OF_HOUSEHOLD_GENDER,
            self::LIVELIHOD,
            self::FOOD_CONSUMPTION_SCORE,
            self::COPING_STRATEGIES_INDEX,
            self::INCOME_LEVEL,
            self::HOUSEHOLD_SIZE,
            self::CURRENT_LOCATION,
            self::LOCATION_TYPE,
            self::CAMP_NAME,
            self::VULNERABILITY_CRITERIA,
            self::COUNTRY_SPECIFIC,
        ];
    }
}
