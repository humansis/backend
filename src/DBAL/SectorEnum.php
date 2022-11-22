<?php

declare(strict_types=1);

namespace DBAL;

class SectorEnum extends AbstractEnum
{
    protected static $values = [
        self::FOOD_SECURITY,
        self::LIVELIHOODS,
        self::MULTIPURPOSE_CASH,
        self::SHELTER,
        self::WASH,
        self::PROTECTION,
        self::EDUCATION_TVET,
        self::EMERGENCY_TELCO,
        self::HEALTH,
        self::LOGISTICS,
        self::NUTRITION,
        self::MINE,
        self::DRR_RESILIENCE,
        self::NON_SECTOR,
        self::CAMP_MANAGEMENT,
        self::EARLY_RECOVERY,
    ];

    public const FOOD_SECURITY = 'Food Security';
    public const LIVELIHOODS = 'Livelihoods';
    public const MULTIPURPOSE_CASH = 'Multi Purpose Cash Assistance';
    public const SHELTER = 'Shelter';
    public const WASH = 'WASH';
    public const PROTECTION = 'Protection';
    public const EDUCATION_TVET = 'Education & TVET';
    public const EMERGENCY_TELCO = 'Emergency Telecomms';
    public const HEALTH = 'Health';
    public const LOGISTICS = 'Logistics';
    public const NUTRITION = 'Nutrition';
    public const MINE = 'Mine Action';
    public const DRR_RESILIENCE = 'DRR & Resilience';
    public const NON_SECTOR = 'Non-Sector Specific';
    public const CAMP_MANAGEMENT = 'Camp Coordination and Management';
    public const EARLY_RECOVERY = 'Early Recovery';

    public function getName()
    {
        return 'enum_sector';
    }

    public static function all(): array
    {
        return self::$values;
    }
}
