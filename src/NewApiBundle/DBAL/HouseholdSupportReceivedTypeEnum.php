<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Enum\HouseholdSupportReceivedType;

class HouseholdSupportReceivedTypeEnum extends \NewApiBundle\DBAL\AbstractEnum
{
    use EnumTrait;

    // unused yet, prepared for future migration
    public function getName(): string
    {
        return 'enum_household_support_received_type';
    }

    public static function all(): array
    {
        return HouseholdSupportReceivedType::values();
    }

    public static function databaseMap(): array
    {
        return [
            0 => HouseholdSupportReceivedType::MPCA,
            1 => HouseholdSupportReceivedType::CASH_FOR_WORK,
            2 => HouseholdSupportReceivedType::FOOD_KIT,
            3 => HouseholdSupportReceivedType::FOOD_VOUCHER,
            4 => HouseholdSupportReceivedType::HYGIENE_KIT,
            5 => HouseholdSupportReceivedType::SHELTER_KIT,
            6 => HouseholdSupportReceivedType::SHELTER_RECONSTRUCTION_SUPPORT,
            7 => HouseholdSupportReceivedType::NON_FOOD_ITEMS,
            8 => HouseholdSupportReceivedType::LIVELIHOODS_SUPPORT,
            9 => HouseholdSupportReceivedType::VOCATIONAL_TRAINING,
            10 => HouseholdSupportReceivedType::NONE,
            11 => HouseholdSupportReceivedType::OTHER,
        ];
    }
}
