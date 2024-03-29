<?php

declare(strict_types=1);

namespace DBAL;

use InvalidArgumentException;

class SubSectorEnum extends AbstractEnum
{
    protected static array $values = [
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
        self::CASH_FOR_WINTERIZATION,

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
        self::CASH_FOR_PROTECTION,

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
        self::CASH_FOR_WINTERIZATION => 'Cash for winterization',

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
        self::CASH_FOR_PROTECTION => 'Cash for protection',

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
    final public const IN_KIND_FOOD = 'in_kind_food';
    final public const FOOD_VOUCHERS = 'food_vouchers';
    final public const CASH_TRANSFERS = 'food_cash_transfers';
    final public const FOOD_CASH_FOR_WORK = 'food_cash_for_work';
    final public const SKILLS_TRAINING = 'skills_training';
    final public const TECHNICAL_SUPPORT = 'technical_support';
    final public const PROVISION_OF_INPUTS = 'provision_of_inputs';
    final public const BUSINESS_GRANTS = 'business_grants';
    final public const AGRICULTURAL_VOUCHERS = 'agricultural_vouchers';
    final public const LIVELIHOOD_CASH_FOR_WORK = 'livelihood_cash_for_work';
    final public const MULTI_PURPOSE_CASH_ASSISTANCE = 'multi_purpose_cash_assistance';
    final public const REHABILITATION = 'rehabilitation';
    final public const CONSTRUCTION = 'construction';
    final public const SETTLEMENT_UPGRADES = 'settlement_upgrades';
    final public const WINTERIZATION_KITS = 'winterization_kits';
    final public const WINTERIZATION_UPGRADES = 'winterization_upgrades';
    final public const SHELTER_KITS = 'shelter_kits';
    final public const NFI_KITS = 'nfi_kits';
    final public const CASH_FOR_SHELTER = 'cash_for_shelter';
    final public const WATER_POINT_REHABILITATION = 'water_point_rehabilitation';
    final public const WATER_POINT_CONSTRUCTION = 'water_point_construction';
    final public const WATER_TRUCKING = 'water_trucking';
    final public const WATER_TREATMENT = 'water_treatment';
    final public const VECTOR_CONTROL = 'vector_control';
    final public const SOLID_WASTE_MANAGEMENT = 'solid_waste_management';
    final public const SANITATION = 'sanitation';
    final public const HYGIENE_PROMOTION = 'hygiene_promotion';
    final public const HYGIENE_KITS = 'hygiene_kits';
    final public const OPERATIONAL_SUPPLIES = 'operational_supplies';
    final public const PROTECTION_PSYCHOSOCIAL_SUPPORT = 'protection_psychosocial_support';
    final public const INDIVIDUAL_PROTECTION_ASSISTANCE = 'individual_protection_assistance';
    final public const COMMUNITY_BASED_INTERVENTIONS = 'community_based_interventions';
    final public const PROTECTION_ADVOCACY = 'protection_advocacy';
    final public const CHILD_PROTECTION = 'child_protection';
    final public const GENDER_BASED_VIOLENCE_ACTIVITIES = 'gender_based_violence_activities';
    final public const TEACHER_INCENTIVE_PAYMENTS = 'teacher_incentive_payments';
    final public const TEACHER_TRAINING = 'teacher_training';
    final public const LEARNING_MATERIALS = 'learning_materials';
    final public const EDUCATION_PSYCHOSOCIAL_SUPPORT = 'education_psychosocial_support';
    final public const LEARNING_SUPPORT = 'learning_support';
    final public const EDUCATION_CASH_FOR_WORK = 'education_cash_for_work';
    final public const PARENT_SESSIONS = 'education_parent_sessions';
    final public const SCHOOL_OPERATIONAL_SUPPORT = 'school_operational_support';
    final public const DEFAULT_EMERGENCY_TELCO = 'default_emergency_telco';
    final public const DEFAULT_HEALTH = 'default_health';
    final public const DEFAULT_LOGISTICS = 'default_logistics';
    final public const DEFAULT_NUTRITION = 'default_nutrition';
    final public const DEFAULT_MINE = 'default_mine';
    final public const DEFAULT_DRR_RESILIENCE = 'default_drr_resilience';
    final public const DEFAULT_NON_SECTOR = 'default_non_sector';
    final public const DEFAULT_CAMP_MANAGEMENT = 'default_camp_management';
    final public const DEFAULT_EARLY_RECOVERY = 'default_early_recovery';
    final public const CASH_FOR_WINTERIZATION = 'cash_for_winterization';
    final public const CASH_FOR_PROTECTION = 'cash_for_protection';

    public function getName(): string
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
