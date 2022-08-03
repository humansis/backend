<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Enum\ModalityType;

class ModalityTypeEnum extends \CommonBundle\DBAL\AbstractEnum
{
    use EnumTrait;

    public function getName(): string
    {
        return 'enum_modality_type';
    }

    public static function all(): array
    {
        return ModalityType::values();
    }

    public static function databaseMap(): array
    {
        return [
            ModalityType::MOBILE_MONEY => ModalityType::MOBILE_MONEY,
            ModalityType::CASH => ModalityType::CASH,
            ModalityType::SMART_CARD => ModalityType::SMART_CARD,
            ModalityType::QR_CODE_VOUCHER => ModalityType::QR_CODE_VOUCHER,
            ModalityType::PAPER_VOUCHER => ModalityType::PAPER_VOUCHER,
            ModalityType::FOOD_RATIONS => ModalityType::FOOD_RATIONS,
            ModalityType::READY_TO_EAT_RATIONS => ModalityType::READY_TO_EAT_RATIONS,
            ModalityType::BREAD => ModalityType::BREAD,
            ModalityType::AGRICULTURAL_KIT => ModalityType::AGRICULTURAL_KIT,
            ModalityType::WASH_KIT => ModalityType::WASH_KIT,
            ModalityType::SHELTER_TOOL_KIT => ModalityType::SHELTER_TOOL_KIT,
            ModalityType::HYGIENE_KIT => ModalityType::HYGIENE_KIT,
            ModalityType::DIGNITY_KIT => ModalityType::DIGNITY_KIT,
            ModalityType::NFI_KIT => ModalityType::NFI_KIT,
            ModalityType::WINTERIZATION_KIT => ModalityType::WINTERIZATION_KIT,
            ModalityType::ACTIVITY_ITEM => ModalityType::ACTIVITY_ITEM,
            ModalityType::LOAN => ModalityType::LOAN,
            ModalityType::BUSINESS_GRANT => ModalityType::BUSINESS_GRANT,
        ];
    }
}
