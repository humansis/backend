<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring\Enum;

use NewApiBundle\Enum\HouseholdShelterStatus;
use NewApiBundle\Enum\HouseholdSupportReceivedType;

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
    public const DISABLED = 'Person with disability';
    public const INFANT = 'Infant';
    public const ELDERLY = 'Elderly';
    public const PREGNANT_LACTATING_FEMALE = 'Pregnant or lactating female';
    public const NO_VULNERABILITY = 'No vulnerability';
    public const OTHER = 'Other';

    public const VERY_LOW_VULNERABILITY = 'Dependency ratio very low vulnerability';
    public const LOW_VULNERABILITY = 'Dependency ratio low vulnerability';
    public const MODERATE_VULNERABILITY = 'Dependency ratio moderate vulnerability';
    public const HIGH_VULNERABILITY = 'Dependency ratio high vulnerability';
    public const VERY_HIGH_VULNERABILITY = 'Dependency ratio very high vulnerability';
    public const EXTREME_VULNERABILITY = 'Dependency ratio extreme vulnerability';

    public const SHELTER_TENT = 'Tent';
    public const SHELTER_MAKESHIFT = 'Makeshift Shelter';
    public const SHELTER_TRANSITIONAL = 'Transitional Shelter';
    public const SHELTER_SEVERELY_DAMAGED = 'House/Apartment - Severely Damaged';
    public const SHELTER_MODERATELY_DAMAGED = 'House/Apartment - Moderately Damaged';
    public const SHELTER_NOT_DAMAGED = 'House/Apartment - Good Condition';
    public const SHELTER_SHARED = 'Room or Space in Shared Accommodation';
    public const SHELTER_OTHER = 'Other';

    public const ASSETS_0_1 = '1 Asset';
    public const ASSETS_2 = '2 Assets';
    public const ASSETS_3 = '3 Assets';
    public const ASSETS_4 = '4 Assets';
    public const ASSETS_5_MORE = '5 Assets or More';

    public const CSI_0_20 = '0 - 19';
    public const CSI_20_30 = '20 - 29';
    public const CSI_30_40 = '30 - 39';
    public const CSI_40_MORE = '40+';

    public const INCOME_SPENT_0_50 = 'spent < 50 %';
    public const INCOME_SPENT_50_65 = '50 % < spent < 65 %';
    public const INCOME_SPENT_65_75 = '65 % < spent < 75 %';
    public const INCOME_SPENT_75_MORE = '75% < spent';

    public const CONSUMPTION_POOR = '0 - 20';
    public const CONSUMPTION_BORDERLINE = '21 - 35';
    public const CONSUMPTION_ACCEPTABLE = '36+';

    public const DEBT_LEVEL_1 = '1';
    public const DEBT_LEVEL_2 = '2';
    public const DEBT_LEVEL_3 = '3';
    public const DEBT_LEVEL_4 = '4';
    public const DEBT_LEVEL_5 = '5';

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
            self::INFANT,
            self::ELDERLY,
            self::PREGNANT_LACTATING_FEMALE,
            self::DISABLED,
            self::NO_VULNERABILITY,
            self::OTHER,
        ],

        ScoringRulesEnum::HH_MEMBERS_VULNERABILITY => [
            self::CHRONICALLY_ILL,
            self::INFANT,
            self::ELDERLY,
            self::PREGNANT_LACTATING_FEMALE,
            self::DISABLED,
            self::NO_VULNERABILITY,
            self::OTHER,

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
            self::SHELTER_TENT,
            self::SHELTER_MAKESHIFT,
            self::SHELTER_TRANSITIONAL,
            self::SHELTER_SEVERELY_DAMAGED,
            self::SHELTER_MODERATELY_DAMAGED,
            self::SHELTER_NOT_DAMAGED,
            self::SHELTER_SHARED,
            self::SHELTER_OTHER,
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

        ScoringRulesEnum::DEBT => [
            self::DEBT_LEVEL_1,
            self::DEBT_LEVEL_2,
            self::DEBT_LEVEL_3,
            self::DEBT_LEVEL_4,
            self::DEBT_LEVEL_5,
        ],

        ScoringRulesEnum::ASSISTANCE_PROVIDED => [
            HouseholdSupportReceivedType::MPCA,
            HouseholdSupportReceivedType::CASH_FOR_WORK,
            HouseholdSupportReceivedType::FOOD_KIT,
            HouseholdSupportReceivedType::FOOD_VOUCHER,
            HouseholdSupportReceivedType::HYGIENE_KIT,
            HouseholdSupportReceivedType::SHELTER_KIT,
            HouseholdSupportReceivedType::SHELTER_RECONSTRUCTION_SUPPORT,
            HouseholdSupportReceivedType::NON_FOOD_ITEMS,
            HouseholdSupportReceivedType::LIVELIHOODS_SUPPORT,
            HouseholdSupportReceivedType::VOCATIONAL_TRAINING,
        ],
    ];
}
