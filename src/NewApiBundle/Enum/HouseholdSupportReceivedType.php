<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

class HouseholdSupportReceivedType
{
    use EnumTrait;

    const MPCA = 'MPCA';
    const CASH_FOR_WORK = 'Cash for Work';
    const FOOD_KIT = 'Food Kit';
    const FOOD_VOUCHER = 'Food Voucher';
    const HYGIENE_KIT = 'Hygiene Kit';
    const SHELTER_KIT = 'Shelter Kit';
    const SHELTER_RECONSTRUCTION_SUPPORT = 'Shelter Reconstruction Support';
    const NON_FOOD_ITEMS = 'Non Food Items';
    const LIVELIHOODS_SUPPORT = 'Livelihoods Support';
    const VOCATIONAL_TRAINING = 'Vocational Training';
    const NONE = 'None';
    const OTHER = 'Other';

    protected static $values = [
        0 => self::MPCA,
        1 => self::CASH_FOR_WORK,
        2 => self::FOOD_KIT,
        3 => self::FOOD_VOUCHER,
        4 => self::HYGIENE_KIT,
        5 => self::SHELTER_KIT,
        6 => self::SHELTER_RECONSTRUCTION_SUPPORT,
        7 => self::NON_FOOD_ITEMS,
        8 => self::LIVELIHOODS_SUPPORT,
        9 => self::VOCATIONAL_TRAINING,
        10 => self::NONE,
        11 => self::OTHER,
    ];

    public static function values(): array
    {
        return array_values(self::$values);
    }
}
