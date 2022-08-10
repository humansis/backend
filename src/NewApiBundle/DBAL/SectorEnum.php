<?php

declare(strict_types=1);

namespace NewApiBundle\DBAL;

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

    const FOOD_SECURITY = 'Food Security';
    const LIVELIHOODS = 'Livelihoods';
    const MULTIPURPOSE_CASH = 'Multi Purpose Cash Assistance';
    const SHELTER = 'Shelter';
    const WASH = 'WASH';
    const PROTECTION = 'Protection';
    const EDUCATION_TVET = 'Education & TVET';
    const EMERGENCY_TELCO = 'Emergency Telecomms';
    const HEALTH = 'Health';
    const LOGISTICS = 'Logistics';
    const NUTRITION = 'Nutrition';
    const MINE = 'Mine Action';
    const DRR_RESILIENCE = 'DRR & Resilience';
    const NON_SECTOR = 'Non-Sector Specific';
    const CAMP_MANAGEMENT = 'Camp Coordination and Management';
    const EARLY_RECOVERY = 'Early Recovery';

    public function getName()
    {
        return 'enum_sector';
    }

    public static function all(): array
    {
        return self::$values;
    }
}
