<?php

declare(strict_types=1);

namespace DBAL;

class SectorEnum extends AbstractEnum
{
    protected static array $values = [
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

    final public const FOOD_SECURITY = 'Food Security';
    final public const LIVELIHOODS = 'Livelihoods';
    final public const MULTIPURPOSE_CASH = 'Multi Purpose Cash Assistance';
    final public const SHELTER = 'Shelter';
    final public const WASH = 'WASH';
    final public const PROTECTION = 'Protection';
    final public const EDUCATION_TVET = 'Education & TVET';
    final public const EMERGENCY_TELCO = 'Emergency Telecomms';
    final public const HEALTH = 'Health';
    final public const LOGISTICS = 'Logistics';
    final public const NUTRITION = 'Nutrition';
    final public const MINE = 'Mine Action';
    final public const DRR_RESILIENCE = 'DRR & Resilience';
    final public const NON_SECTOR = 'Non-Sector Specific';
    final public const CAMP_MANAGEMENT = 'Camp Coordination and Management';
    final public const EARLY_RECOVERY = 'Early Recovery';

    public function getName(): string
    {
        return 'enum_sector';
    }

    public static function all(): array
    {
        return self::$values;
    }
}
