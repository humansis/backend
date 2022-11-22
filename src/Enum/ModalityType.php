<?php

declare(strict_types=1);

namespace Enum;

use function in_array;

final class ModalityType
{
    use EnumTrait;

    public const MOBILE_MONEY = 'Mobile Money';
    public const CASH = 'Cash';
    public const SMART_CARD = 'Smartcard';
    public const QR_CODE_VOUCHER = 'QR Code Voucher';
    public const PAPER_VOUCHER = 'Paper Voucher';
    public const FOOD_RATIONS = 'Food Rations';
    public const READY_TO_EAT_RATIONS = 'Ready to Eat Rations';
    public const BREAD = 'Bread';
    public const AGRICULTURAL_KIT = 'Agricultural Kit';
    public const WASH_KIT = 'WASH Kit';
    public const SHELTER_TOOL_KIT = 'Shelter tool kit';
    public const HYGIENE_KIT = 'Hygiene kit';
    public const DIGNITY_KIT = 'Dignity kit';
    public const NFI_KIT = 'NFI Kit';
    public const WINTERIZATION_KIT = 'Winterization Kit';
    public const ACTIVITY_ITEM = 'Activity item';
    public const LOAN = 'Loan';
    public const LOAN_TEST = 'Loan Test';
    public const BUSINESS_GRANT = 'Business Grant';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [
            self::MOBILE_MONEY,
            self::CASH,
            self::SMART_CARD,
            self::QR_CODE_VOUCHER,
            self::PAPER_VOUCHER,
            self::FOOD_RATIONS,
            self::READY_TO_EAT_RATIONS,
            self::BREAD,
            self::AGRICULTURAL_KIT,
            self::WASH_KIT,
            self::SHELTER_TOOL_KIT,
            self::HYGIENE_KIT,
            self::DIGNITY_KIT,
            self::NFI_KIT,
            self::WINTERIZATION_KIT,
            self::ACTIVITY_ITEM,
            self::LOAN,
            self::LOAN_TEST,
            self::BUSINESS_GRANT,
        ];
    }

    public static function getModality(string $modalityType): ?string
    {
        if (!in_array($modalityType, self::values(), true)) {
            return null;
        }

        foreach (Modality::values() as $modalityValue) {
            if (in_array($modalityType, Modality::getModalityTypes($modalityValue))) {
                return $modalityValue;
            }
        }

        return null;
    }
}
