<?php declare(strict_types=1);

namespace NewApiBundle\Enum;

final class SelectionCriteriaField
{
    public const
        GENDER = 'gender',
        DATE_OF_BIRTH = 'dateOfBirth',
        RESIDENCY_STATUS = 'residencyStatus',
        HAS_NOT_BEEN_IN_DISTRIBUTIONS_SINCE = 'hasNotBeenInDistributionsSince',
        HEAD_OF_HOUSEHOLD_WITH_DISABILITY = 'headOfHouseholdWithDisability',
        HAS_VALID_SMARTCARD = 'hasValidSmartcard',
        HEAD_OF_HOUSEHOLD_DATE_OF_BIRTH = 'headOfHouseholdDateOfBirth',
        HEAD_OF_HOUSEHOLD_GENDER = 'headOfHouseholdGender',
        LIVELIHOD = 'livelihood',
        FOOD_CONSUMPTION_SCORE = 'foodConsumptionScore',
        COPING_STRATEGIES_INDEX = 'copingStrategiesIndex',
        INCOME_LEVEL = 'incomeLevel',
        HOUSEHOLD_SIZE = 'householdSize',
        CURRENT_LOCATION = 'currentLocation',
        CURRENT_ADM_1 = 'currentAdm1',
        CURRENT_ADM_2 = 'currentAdm2',
        CURRENT_ADM_3 = 'currentAdm3',
        CURRENT_ADM_4 = 'currentAdm4',
        LOCATION_TYPE = 'locationType',
        CAMP_NAME = 'campName',
        VULNERABILITY_CRITERIA = 'vulnerabilityCriteria',
        COUNTRY_SPECIFIC = 'countrySpecific';
}
