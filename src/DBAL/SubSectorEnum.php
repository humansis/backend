<?php

declare(strict_types=1);

namespace DBAL;

use InvalidArgumentException;

class SubSectorEnum extends AbstractEnum
{
    protected static $values = [
        self::IN_KIND_FOOD,
        self::CASH_TRANSFERS,
        self::FOOD_VOUCHERS,
        self::FOOD_CASH_FOR_WORK,

        self::SKILLS_TRAINING,
        self::TECHNICAL_SUPPORT,
        self::PROVISION_OF_INPUTS,
        self::BUSINESS_GRANTS,
        self::AGRICULTURAL_VOUCHERS,
        self::LIVELIHOOD_CASH_FOR_WORK,

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
        self::LEARNING_SUPPORT,
        self::EDUCATION_CASH_FOR_WORK,
        self::PARENT_SESSIONS,
        self::SCHOOL_OPERATIONAL_SUPPORT,

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

    private const TRANSLATIONS = [
        self::IN_KIND_FOOD => 'In-kind Food',
        self::CASH_TRANSFERS => 'Cash Transfers',
        self::FOOD_VOUCHERS => 'Food Vouchers',
        self::FOOD_CASH_FOR_WORK => 'Cash for Work',

        self::SKILLS_TRAINING => 'Skills Training',
        self::TECHNICAL_SUPPORT => 'Technical Support',
        self::PROVISION_OF_INPUTS => 'Provision of Inputs',
        self::BUSINESS_GRANTS => 'Business Grants',
        self::AGRICULTURAL_VOUCHERS => 'Agricultural Vouchers',
        self::LIVELIHOOD_CASH_FOR_WORK => 'Cash for Work',

        self::MULTI_PURPOSE_CASH_ASSISTANCE => 'Multi Purpose Cash Assistance',

        self::REHABILITATION => 'Rehabilitation (Light, Medium, Heavy)',
        self::CONSTRUCTION => 'Construction (Light, Medium, Heavy)',
        self::SETTLEMENT_UPGRADES => 'Settlement Upgrades',
        self::WINTERIZATION_KITS => 'Winterization Kits or Materials',
        self::WINTERIZATION_UPGRADES => 'Winterization Upgrades',
        self::SHELTER_KITS => 'Shelter Kits',
        self::NFI_KITS => 'NFI Kits',
        self::CASH_FOR_SHELTER => 'Cash for Shelter',

        self::WATER_POINT_REHABILITATION => 'Water Point Rehabilitation',
        self::WATER_POINT_CONSTRUCTION => 'Water Point Construction',
        self::WATER_TRUCKING => 'Water Trucking',
        self::WATER_TREATMENT => 'Water Treatment',

        self::VECTOR_CONTROL => 'Vector Control',

        self::SOLID_WASTE_MANAGEMENT => 'Solid Waste Management',
        self::SANITATION => 'Sanitation',
        self::HYGIENE_PROMOTION => 'Hygiene Promotion',
        self::HYGIENE_KITS => 'Hygiene Kits',
        self::OPERATIONAL_SUPPLIES => 'Operational Supplies',

        self::PROTECTION_PSYCHOSOCIAL_SUPPORT => 'Psychosocial Support',
        self::INDIVIDUAL_PROTECTION_ASSISTANCE => 'Individual Protection Assistance',
        self::COMMUNITY_BASED_INTERVENTIONS => 'Community Based Interventions',
        self::PROTECTION_ADVOCACY => 'Protection Advocacy',
        self::CHILD_PROTECTION => 'Child Protection',
        self::GENDER_BASED_VIOLENCE_ACTIVITIES => 'Gender Based Violence Activities',

        self::TEACHER_INCENTIVE_PAYMENTS => 'Teacher Incentive Payments',
        self::TEACHER_TRAINING => 'Teacher Training',
        self::LEARNING_MATERIALS => 'Learning Materials',
        self::EDUCATION_PSYCHOSOCIAL_SUPPORT => 'Psychosocial Support',
        self::LEARNING_SUPPORT => 'Learning Support',
        self::EDUCATION_CASH_FOR_WORK => 'Cash for Work',
        self::PARENT_SESSIONS => 'Sessions for Parents and Caregivers',
        self::SCHOOL_OPERATIONAL_SUPPORT => 'School Operational Support',

        self::DEFAULT_EMERGENCY_TELCO => 'Default Emergency Telecomms',
        self::DEFAULT_HEALTH => 'Default Health',
        self::DEFAULT_LOGISTICS => 'Default Logistics',
        self::DEFAULT_NUTRITION => 'Default Nutrition',
        self::DEFAULT_MINE => 'Default Mine Action',
        self::DEFAULT_DRR_RESILIENCE => 'Default DRR & Resilience',
        self::DEFAULT_NON_SECTOR => 'Default Non-Sector Specific',
        self::DEFAULT_CAMP_MANAGEMENT => 'Default Camp Coordination and Management',
        self::DEFAULT_EARLY_RECOVERY => 'Default Early Recovery',
    ];
    public const IN_KIND_FOOD = 'in_kind_food';
    public const FOOD_VOUCHERS = 'food_vouchers';
    public const CASH_TRANSFERS = 'food_cash_transfers';
    public const FOOD_CASH_FOR_WORK = 'food_cash_for_work';
    public const SKILLS_TRAINING = 'skills_training';
    public const TECHNICAL_SUPPORT = 'technical_support';
    public const PROVISION_OF_INPUTS = 'provision_of_inputs';
    public const BUSINESS_GRANTS = 'business_grants';
    public const AGRICULTURAL_VOUCHERS = 'agricultural_vouchers';
    public const LIVELIHOOD_CASH_FOR_WORK = 'livelihood_cash_for_work';
    public const MULTI_PURPOSE_CASH_ASSISTANCE = 'multi_purpose_cash_assistance';
    public const REHABILITATION = 'rehabilitation';
    public const CONSTRUCTION = 'construction';
    public const SETTLEMENT_UPGRADES = 'settlement_upgrades';
    public const WINTERIZATION_KITS = 'winterization_kits';
    public const WINTERIZATION_UPGRADES = 'winterization_upgrades';
    public const SHELTER_KITS = 'shelter_kits';
    public const NFI_KITS = 'nfi_kits';
    public const CASH_FOR_SHELTER = 'cash_for_shelter';
    public const WATER_POINT_REHABILITATION = 'water_point_rehabilitation';
    public const WATER_POINT_CONSTRUCTION = 'water_point_construction';
    public const WATER_TRUCKING = 'water_trucking';
    public const WATER_TREATMENT = 'water_treatment';
    public const VECTOR_CONTROL = 'vector_control';
    public const SOLID_WASTE_MANAGEMENT = 'solid_waste_management';
    public const SANITATION = 'sanitation';
    public const HYGIENE_PROMOTION = 'hygiene_promotion';
    public const HYGIENE_KITS = 'hygiene_kits';
    public const OPERATIONAL_SUPPLIES = 'operational_supplies';
    public const PROTECTION_PSYCHOSOCIAL_SUPPORT = 'protection_psychosocial_support';
    public const INDIVIDUAL_PROTECTION_ASSISTANCE = 'individual_protection_assistance';
    public const COMMUNITY_BASED_INTERVENTIONS = 'community_based_interventions';
    public const PROTECTION_ADVOCACY = 'protection_advocacy';
    public const CHILD_PROTECTION = 'child_protection';
    public const GENDER_BASED_VIOLENCE_ACTIVITIES = 'gender_based_violence_activities';
    public const TEACHER_INCENTIVE_PAYMENTS = 'teacher_incentive_payments';
    public const TEACHER_TRAINING = 'teacher_training';
    public const LEARNING_MATERIALS = 'learning_materials';
    public const EDUCATION_PSYCHOSOCIAL_SUPPORT = 'education_psychosocial_support';
    public const LEARNING_SUPPORT = 'learning_support';
    public const EDUCATION_CASH_FOR_WORK = 'education_cash_for_work';
    public const PARENT_SESSIONS = 'education_parent_sessions';
    public const SCHOOL_OPERATIONAL_SUPPORT = 'school_operational_support';
    public const DEFAULT_EMERGENCY_TELCO = 'default_emergency_telco';
    public const DEFAULT_HEALTH = 'default_health';
    public const DEFAULT_LOGISTICS = 'default_logistics';
    public const DEFAULT_NUTRITION = 'default_nutrition';
    public const DEFAULT_MINE = 'default_mine';
    public const DEFAULT_DRR_RESILIENCE = 'default_drr_resilience';
    public const DEFAULT_NON_SECTOR = 'default_non_sector';
    public const DEFAULT_CAMP_MANAGEMENT = 'default_camp_management';
    public const DEFAULT_EARLY_RECOVERY = 'default_early_recovery';

    public function getName()
    {
        return 'enum_sub_sector';
    }

    public static function all(): array
    {
        return self::$values;
    }

    public static function translate(string $livelihood): string
    {
        if (!array_key_exists($livelihood, self::TRANSLATIONS)) {
            throw new InvalidArgumentException("$livelihood is not valid Livelihood value.");
        }

        return self::TRANSLATIONS[$livelihood];
    }
}
