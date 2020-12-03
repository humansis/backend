<?php

declare(strict_types=1);

namespace ProjectBundle\DBAL;

use CommonBundle\DBAL\AbstractEnum;

class SubSectorEnum extends AbstractEnum
{
    protected static $values = [
        self::FOOD_PARCELS_BASKETS,
        self::RTER,
        self::FOOD_VOUCHERS,
        self::CASH_FOR_WORK,

        self::SKILLS_TRAINING,
        self::TECHNICAL_SUPPORT,
        self::DISTRIBUTION_OF_INPUTS,
        self::BUSINESS_GRANTS,
        self::AGRICULTURAL_VOUCHERS,

        self::MULTI_PURPOSE_CASH_ASSISTANCE,

        self::REHABILITATION,
        self::CONSTRUCTION,
        self::SETTLEMENT_UPGRADES,
        self::WINTERIZATION_KITS,
        self::WINTERIZATION_UPGRADES,
        self::SHELTER_KITS,
        self::NFI_KITS,
        self::CASH_FOR_SHELTER,

        self::WATER_POINT_REHABILITATION,
        self::WATER_POINT_CONSTRUCTION,
        self::WATER_TRUCKING,
        self::WATER_TREATMENT,

        self::VECTOR_CONTROL,

        self::SOLID_WASTE_MANAGEMENT,
        self::SANITATION,
        self::HYGIENE_PROMOTION,
        self::HYGIENE_KITS,
        self::OPERATIONAL_SUPPLIES,

        self::PROTECTION_PSYCHOSOCIAL_SUPPORT,
        self::INDIVIDUAL_PROTECTION_ASSISTANCE,
        self::COMMUNITY_BASED_INTERVENTIONS,
        self::PROTECTION_ADVOCACY,
        self::CHILD_PROTECTION,
        self::GENDER_BASED_VIOLENCE_ACTIVITIES,

        self::TEACHER_INCENTIVE_PAYMENTS,
        self::TEACHER_TRAINING,
        self::LEARNING_MATERIALS,
        self::EDUCATION_PSYCHOSOCIAL_SUPPORT,
        self::EDUCATION_SERVICES,

        self::DEFAULT_EMERGENCY_TELCO,
        self::DEFAULT_HEALTH,
        self::DEFAULT_LOGISTICS,
        self::DEFAULT_NUTRITION,
        self::DEFAULT_MINE,
        self::DEFAULT_DRR_RESILIENCE,
        self::DEFAULT_NON_SECTOR,
        self::DEFAULT_CAMP_MANAGEMENT,
        self::DEFAULT_EARLY_RECOVERY,
    ];

    const FOOD_PARCELS_BASKETS = 'food_parcels_baskets';
    const RTER = 'rter';
    const FOOD_VOUCHERS = 'food_vouchers';
    const CASH_FOR_WORK = 'cash_for_work';

    const SKILLS_TRAINING = 'skills_training';
    const TECHNICAL_SUPPORT = 'technical_support';
    const DISTRIBUTION_OF_INPUTS = 'distribution_of_inputs';
    const BUSINESS_GRANTS = 'business_grants';
    const AGRICULTURAL_VOUCHERS = 'agricultural_vouchers';

    const MULTI_PURPOSE_CASH_ASSISTANCE = 'multi_purpose_cash_assistance';

    const REHABILITATION = 'rehabilitation';
    const CONSTRUCTION = 'construction';
    const SETTLEMENT_UPGRADES = 'settlement_upgrades';
    const WINTERIZATION_KITS = 'winterization_kits';
    const WINTERIZATION_UPGRADES = 'winterization_upgrades';
    const SHELTER_KITS = 'shelter_kits';
    const NFI_KITS = 'nfi_kits';
    const CASH_FOR_SHELTER = 'cash_for_shelter';

    const WATER_POINT_REHABILITATION = 'water_point_rehabilitation';
    const WATER_POINT_CONSTRUCTION = 'water_point_construction';
    const WATER_TRUCKING = 'water_trucking';
    const WATER_TREATMENT = 'water_treatment';
    const VECTOR_CONTROL = 'vector_control';
    const SOLID_WASTE_MANAGEMENT = 'solid_waste_management';
    const SANITATION = 'sanitation';
    const HYGIENE_PROMOTION = 'hygiene_promotion';
    const HYGIENE_KITS = 'hygiene_kits';
    const OPERATIONAL_SUPPLIES = 'operational_supplies';

    const PROTECTION_PSYCHOSOCIAL_SUPPORT = 'protection_psychosocial_support';
    const INDIVIDUAL_PROTECTION_ASSISTANCE = 'individual_protection_assistance';
    const COMMUNITY_BASED_INTERVENTIONS = 'community_based_interventions';
    const PROTECTION_ADVOCACY = 'protection_advocacy';
    const CHILD_PROTECTION = 'child_protection';
    const GENDER_BASED_VIOLENCE_ACTIVITIES = 'gender_based_violence_activities';

    const TEACHER_INCENTIVE_PAYMENTS = 'teacher_incentive_payments';
    const TEACHER_TRAINING = 'teacher_training';
    const LEARNING_MATERIALS = 'learning_materials';
    const EDUCATION_PSYCHOSOCIAL_SUPPORT = 'education_psychosocial_support';
    const EDUCATION_SERVICES = 'education_services';

    const DEFAULT_EMERGENCY_TELCO = 'default_emergency_telco';
    const DEFAULT_HEALTH = 'default_health';
    const DEFAULT_LOGISTICS = 'default_logistics';
    const DEFAULT_NUTRITION = 'default_nutrition';
    const DEFAULT_MINE = 'default_mine';
    const DEFAULT_DRR_RESILIENCE = 'default_drr_resilience';
    const DEFAULT_NON_SECTOR = 'default_non_sector';
    const DEFAULT_CAMP_MANAGEMENT = 'default_camp_management';
    const DEFAULT_EARLY_RECOVERY = 'default_early_recovery';

    public function getName()
    {
        return 'enum_sub_sector';
    }

    public static function all(): array
    {
        return self::$values;
    }
}
