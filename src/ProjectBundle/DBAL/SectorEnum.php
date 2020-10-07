<?php
namespace ProjectBundle\DBAL;

use CommonBundle\DBAL\AbstractEnum;

class SectorEnum extends AbstractEnum
{
    protected $name = 'enum_sector';
    protected static $values = [
        self::FOOD_SECURITY,
        self::LIVELIHOODS,
        self::MULTIPURPOSE_CASH,
        self::SHELTER,
        self::WASH,
        self::PROTECTION,
        self::EDUCATION,
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

    const FOOD_SECURITY = "food_security";
    const LIVELIHOODS = "livelihoods";
    const MULTIPURPOSE_CASH = "multipurpose_cash";
    const SHELTER = "shelter";
    const WASH = "wash";
    const PROTECTION = "protection";
    const EDUCATION = "education";
    const EMERGENCY_TELCO = "emergency_telco";
    const HEALTH = "health";
    const LOGISTICS = "logistics";
    const NUTRITION = "nutrition";
    const MINE = "mine";
    const DRR_RESILIENCE = "drr_resilience";
    const NON_SECTOR = "non_sector";
    const CAMP_MANAGEMENT = "camp_management";
    const EARLY_RECOVERY = "early_recovery";

    public static function all(): array
    {
        return self::$values;
    }
}
