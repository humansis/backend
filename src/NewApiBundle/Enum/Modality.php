<?php

declare(strict_types=1);

namespace NewApiBundle\Enum;

use function array_merge;

final class Modality
{
    use EnumTrait;

    public const CASH    = 'Cash';
    public const VOUCHER = 'Voucher';
    public const IN_KIND = 'In Kind';
    public const OTHER   = 'Other';

    public const CASH_TYPES    = [ModalityType::MOBILE_MONEY, ModalityType::CASH, ModalityType::SMART_CARD];
    public const VOUCHER_TYPES = [ModalityType::QR_CODE_VOUCHER, ModalityType::PAPER_VOUCHER];
    public const IN_KIND_TYPES = [
        ModalityType::FOOD_RATIONS,
        ModalityType::READY_TO_EAT_RATIONS,
        ModalityType::BREAD,
        ModalityType::AGRICULTURAL_KIT,
        ModalityType::WASH_KIT,
        ModalityType::SHELTER_TOOL_KIT,
        ModalityType::HYGIENE_KIT,
        ModalityType::DIGNITY_KIT,
        ModalityType::NFI_KIT,
        ModalityType::WINTERIZATION_KIT,
        ModalityType::ACTIVITY_ITEM,
    ];
    public const OTHER_TYPES   = [ModalityType::LOAN, ModalityType::BUSINESS_GRANT];

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [
            self::CASH,
            self::VOUCHER,
            self::IN_KIND,
            self::OTHER,
        ];
    }

    public static function getModalityTypes(?string $modality = null): array
    {
        switch ($modality) {
            case self::CASH:
                return self::CASH_TYPES;

            case self::VOUCHER:
                return self::VOUCHER_TYPES;

            case self::IN_KIND:
                return self::IN_KIND_TYPES;

            case self::OTHER:
                return self::OTHER_TYPES;

            case null:
                return array_merge(self::CASH_TYPES, self::VOUCHER_TYPES, self::IN_KIND_TYPES, self::OTHER_TYPES);

            default:
                return [];
        }
    }
}
