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

    public static function getModalityTypes(?string $modality = null, bool $includeInternal = false): array
    {
        $modalityTypes = [];
        switch ($modality) {
            case self::CASH:
                $modalityTypes = self::CASH_TYPES;
                break;

            case self::VOUCHER:
                $modalityTypes = self::VOUCHER_TYPES;
                break;

            case self::IN_KIND:
                $modalityTypes = self::IN_KIND_TYPES;
                break;

            case self::OTHER:
                $modalityTypes = self::OTHER_TYPES;
                break;

            case null:
                $modalityTypes = array_merge(self::CASH_TYPES, self::VOUCHER_TYPES, self::IN_KIND_TYPES, self::OTHER_TYPES);
                break;
        }

        return $includeInternal ? $modalityTypes : array_intersect($modalityTypes, ModalityType::getPublicValues());
    }
}
