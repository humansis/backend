<?php
namespace ProjectBundle\DBAL;

use CommonBundle\DBAL\AbstractEnum;

class SectorEnum extends AbstractEnum
{
    protected $name = 'enum_sector';
    protected $values = [
        self::CAMP_MANAGEMENT,
        self::EARLY_RECOVERY,
        self::EDUCATION,
        self::EMERGENCY_TELCO,
        self::FOOD_SECURITY,
        self::HEALTH,
        self::LOGISTICS,
        self::NUTRITION,
        self::PROTECTION,
        self::SHELTER,
        self::CASH_FOR_WORK,
        self::TVET,
        self::FOOD_RTE,
        self::NFIS,
        self::WASH,
    ];

    const CAMP_MANAGEMENT = "camp_management";
    const EARLY_RECOVERY = "early_recovery";
    const EDUCATION = "education";
    const EMERGENCY_TELCO = "emergency_telco";
    const FOOD_SECURITY = "food_security";
    const HEALTH = "health";
    const LOGISTICS = "logistics";
    const NUTRITION = "nutrition";
    const PROTECTION = "protection";
    const SHELTER = "shelter";
    const CASH_FOR_WORK = "cash_for_work";
    const TVET = "tvet";
    const FOOD_RTE = "food_rte";
    const NFIS = "nfis";
    const WASH = "wash";

    public static function getLabel($value): string
    {
        switch ($value) {
            case self::CAMP_MANAGEMENT:
                return "camp coordination and management";
            case self::EARLY_RECOVERY:
                return "early recovery";
            case self::EDUCATION:
                return "education";
            case self::EMERGENCY_TELCO:
                return "emergency telecommunications";
            case self::FOOD_SECURITY:
                return "food security";
            case self::HEALTH:
                return "health";
            case self::LOGISTICS:
                return "logistics";
            case self::NUTRITION:
                return "nutrition";
            case self::PROTECTION:
                return "protection";
            case self::SHELTER:
                return "shelter";
            case self::CASH_FOR_WORK:
                return "cash for work";
            case self::TVET:
                return "tvet";
            case self::FOOD_RTE:
                return "food, rte kits";
            case self::NFIS:
                return "nfis";
            case self::WASH:
                return "wash";
            default:
                return $value;
        }
    }
}
