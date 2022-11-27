<?php

declare(strict_types=1);

namespace Utils;

use Entity\Assistance;
use Entity\Beneficiary;
use Entity\NationalId;
use Entity\Phone;
use Enum\NationalIdType;
use IntlDateFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;
use Component\Country\Country;

class SmartcardPurchasedItemTransformData
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @param $purchasedItems
     * @param Country $country
     *
     * @return array
     */
    public function transformData($purchasedItems, Country $country): array
    {
        $exportableTable = [];
        $dateFormatter = new IntlDateFormatter(
            $this->translator->getLocale(),
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE
        );
        foreach ($purchasedItems as $purchasedItem) {
            $beneficiary = $purchasedItem->getBeneficiary();
            $assistance = $purchasedItem->getAssistance();
            $datetime = $purchasedItem->getDatePurchase();
            $fullLocation = self::adms($assistance);
            $primaryNationalId = $beneficiary->getPerson()->getPrimaryNationalId();
            $secondaryNationalId = $beneficiary->getPerson()->getSecondaryNationalId();
            $tertiaryNationalId = $beneficiary->getPerson()->getTertiaryNationalId();

            $exportableTable [] = [
                $this->translator->trans('Household ID') => $purchasedItem->getHousehold()->getId(),
                $this->translator->trans('Beneficiary ID') => $beneficiary->getId(),
                $this->translator->trans('Beneficiary First Name (local)') => $beneficiary->getPerson()->getLocalGivenName(),
                $this->translator->trans('Beneficiary Family Name (local)') => $beneficiary->getPerson()->getLocalFamilyName(),
                $this->translator->trans('Primary ID Type') => $primaryNationalId ? $this->translator->trans($primaryNationalId->getIdType()) : $this->translator->trans('N/A'),
                $this->translator->trans('Primary ID Number') => $primaryNationalId ? $primaryNationalId->getIdNumber() : $this->translator->trans('N/A'),
                $this->translator->trans('Secondary ID Type') => $secondaryNationalId ? $this->translator->trans($secondaryNationalId->getIdType()) : $this->translator->trans('N/A'),
                $this->translator->trans('Secondary ID Number') => $secondaryNationalId ? $secondaryNationalId->getIdNumber() : $this->translator->trans('N/A'),
                $this->translator->trans('Tertiary ID Type') => $tertiaryNationalId ? $this->translator->trans($tertiaryNationalId->getIdType()) : $this->translator->trans('N/A'),
                $this->translator->trans('Tertiary ID Number') => $tertiaryNationalId ? $tertiaryNationalId->getIdNumber() : $this->translator->trans('N/A'),
                $this->translator->trans('Phone') => $beneficiary->getPerson()->getFirstPhoneWithPrefix() ?? $this->translator->trans('N/A'),
                $this->translator->trans('Project Name') => $purchasedItem->getProject()->getName(),
                $this->translator->trans('Distribution Name') => $assistance->getName(),
                $this->translator->trans('Round') => $assistance->getRound() ?? $this->translator->trans('N/A'),
                $this->translator->trans($country->getAdm1Name()) => $fullLocation[0],
                $this->translator->trans($country->getAdm2Name()) => $fullLocation[1],
                $this->translator->trans($country->getAdm3Name()) => $fullLocation[2],
                $this->translator->trans($country->getAdm4Name()) => $fullLocation[3],
                $this->translator->trans('Purchase Date & Time') => $datetime ? $dateFormatter->format($datetime) : $this->translator->trans('N/A'),
                $this->translator->trans('Smartcard code') => $purchasedItem->getSmartcardCode() ?? $this->translator->trans('N/A'),
                $this->translator->trans('Item Purchased') => $purchasedItem->getProduct()->getName(),
                $this->translator->trans('Unit') => $purchasedItem->getProduct()->getUnit(),
                $this->translator->trans('Total Cost') => $purchasedItem->getValue(),
                $this->translator->trans('Currency') => $purchasedItem->getCurrency(),
                $this->translator->trans('Vendor Name') => $purchasedItem->getVendor()->getName() ?? $this->translator->trans('N/A'),
                $this->translator->trans('Vendor Humansis ID') => $purchasedItem->getVendor()->getId(),
                $this->translator->trans('Vendor Nr.') => $purchasedItem->getVendor()->getVendorNo() ?? $this->translator->trans('N/A'),
                $this->translator->trans('Humansis Invoice Nr.') => $purchasedItem->getInvoiceNumber() ?? $this->translator->trans('N/A'),
            ];
        }

        return $exportableTable;
    }

    //TODO: fullLocationNames - move to a helper class?
    private static function adms(Assistance $assistance): array
    {
        $location = $assistance->getLocation();
        $names = array_fill(0, 4, null);

        while ($location) {
            $names[$location->getLvl() - 1] = $location->getName();
            $location = $location->getParent();
        }

        return $names;
    }
}
