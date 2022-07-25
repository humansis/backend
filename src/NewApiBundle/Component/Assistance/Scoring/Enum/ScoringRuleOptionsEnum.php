<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring\Enum;

use NewApiBundle\Enum\HouseholdShelterStatus;

/**
 * List of supported values for each calculation rules
 */
final class ScoringRuleOptionsEnum
{
    public const VULNERABILITY_SOLO_PARENT = 'Solo parent';

    public const VULNERABILITY_PREGNANT_OR_LACTATING = 'Pregnant or lactating';

    public const DEPENDENCY_RATIO_MID = '1 (mid.)';
    public const DEPENDENCY_RATIO_HIGH = '>1 (hight dep.)';

    public const CHRONICALLY_ILL_ONE = 'one';
    public const CHRONICALLY_ILL_TWO_OR_MORE = 'two and more';

    public const CHRONICALLY_ILL = 'Chronically ill';
    public const AGE_18 = 'Age < 18';
    public const AGE_59 = 'Age > 59';
    public const PREGNANT_LACTATING_FEMALE = 'Pregnant or lactating female';
    public const DISABLED = 'Disabled';
    public const NO_VULNERABILITY = 'No vulnerability';

    public const VERY_LOW_VULNERABILITY = 'Dependency ratio very low vulnerability';
    public const LOW_VULNERABILITY = 'Dependency ratio low vulnerability';
    public const MODERATE_VULNERABILITY = 'Dependency ratio moderate vulnerability';
    public const HIGH_VULNERABILITY = 'Dependency ratio high vulnerability';
    public const VERY_HIGH_VULNERABILITY = 'Dependency ratio very high vulnerability';
    public const EXTREME_VULNERABILITY = 'Dependency ratio extreme vulnerability';

    public const ASSETS_0_1 = '1 Asset';
    public const ASSETS_2 = '2 Assets';
    public const ASSETS_3 = '3 Assets';
    public const ASSETS_4 = '4 Assets';
    public const ASSETS_5_MORE = '5 Assets or More';

    public const CSI_0_20 = 'No coping';
    public const CSI_20_30 = 'Stress coping';
    public const CSI_30_40 = 'Crisis coping';
    public const CSI_40_MORE = 'Emergeny coping';

    public const INCOME_SPENT_0_50 = 'spent < 50 %';
    public const INCOME_SPENT_50_65 = '50 % < spent < 65 %';
    public const INCOME_SPENT_65_75 = '65 % < spent < 75 %';
    public const INCOME_SPENT_75_MORE = '75% < spent';

    public const CONSUMPTION_POOR = 'Poor Consumption';
    public const CONSUMPTION_BORDERLINE = 'Borderline Consumption';
    public const CONSUMPTION_ACCEPTABLE = 'Acceptable Consumption';

    public const GENDER_MALE = 'Male';
    public const GENDER_FEMALE = 'Female';

    public const SUPPORTED = [
        ScoringRulesEnum::SINGLE_PARENT_HEADED => [
            self::VULNERABILITY_SOLO_PARENT,
        ],

        ScoringRulesEnum::PREGNANT_OR_LACTATING => [
            self::VULNERABILITY_PREGNANT_OR_LACTATING,
        ],

        ScoringRulesEnum::DEPENDENCY_RATIO_UKR => [
            self::DEPENDENCY_RATIO_MID,
            self::DEPENDENCY_RATIO_HIGH,
        ],

        ScoringRulesEnum::NO_OF_CHRONICALLY_ILL => [
            self::CHRONICALLY_ILL_ONE,
            self::CHRONICALLY_ILL_TWO_OR_MORE,
        ],

        ScoringRulesEnum::HH_HEAD_VULNERABILITY => [
            self::CHRONICALLY_ILL,
            self::AGE_18,
            self::AGE_59,
            self::PREGNANT_LACTATING_FEMALE,
            self::DISABLED,
            self::NO_VULNERABILITY,
        ],

        ScoringRulesEnum::HH_MEMBERS_VULNERABILITY => [
            self::CHRONICALLY_ILL,
            self::PREGNANT_LACTATING_FEMALE,
            self::DISABLED,
            self::NO_VULNERABILITY,
        ],

        ScoringRulesEnum::COMPLEX_DEPENDENCY_RATIO => [
            self::VERY_LOW_VULNERABILITY,
            self::LOW_VULNERABILITY,
            self::MODERATE_VULNERABILITY,
            self::HIGH_VULNERABILITY,
            self::VERY_HIGH_VULNERABILITY,
            self::EXTREME_VULNERABILITY,
        ],

        ScoringRulesEnum::SHELTER_TYPE => [
            HouseholdShelterStatus::TENT,
            HouseholdShelterStatus::MAKESHIFT_SHELTER,
            HouseholdShelterStatus::TRANSITIONAL_SHELTER,
            HouseholdShelterStatus::HOUSE_APARTMENT_SEVERELY_DAMAGED,
            HouseholdShelterStatus::HOUSE_APARTMENT_MODERATELY_DAMAGED,
            HouseholdShelterStatus::HOUSE_APARTMENT_NOT_DAMAGED,
            HouseholdShelterStatus::ROOM_OR_SPACE_IN_PUBLIC_BUILDING,
        ],

        ScoringRulesEnum::ASSETS => [
            self::ASSETS_0_1,
            self::ASSETS_2,
            self::ASSETS_3,
            self::ASSETS_4,
            self::ASSETS_5_MORE,
        ],

        ScoringRulesEnum::CSI => [
            self::CSI_0_20,
            self::CSI_20_30,
            self::CSI_30_40,
            self::CSI_40_MORE,
        ],

        ScoringRulesEnum::INCOME_SPENT_ON_FOOD => [
            self::INCOME_SPENT_0_50,
            self::INCOME_SPENT_50_65,
            self::INCOME_SPENT_65_75,
            self::INCOME_SPENT_75_MORE,
        ],

        ScoringRulesEnum::FCS => [
            self::CONSUMPTION_POOR,
            self::CONSUMPTION_BORDERLINE,
            self::CONSUMPTION_ACCEPTABLE,
        ],

        ScoringRulesEnum::HH_HEAD_GENDER => [
            self::GENDER_FEMALE,
            self::GENDER_MALE,
        ],
    ];
}
