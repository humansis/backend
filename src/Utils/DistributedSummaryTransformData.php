<?php

declare(strict_types=1);

namespace Utils;

use Component\Country\Country;
use IntlDateFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;

class DistributedSummaryTransformData
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @param         $distributedItems
     * @param Country $country
     *
     * @return array
     */
    public function transformData($distributedItems, Country $country): array
    {
        $exportableTable = [];
        $dateFormatter = new IntlDateFormatter(
            $this->translator->getLocale(),
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE
        );
        foreach ($distributedItems as $distributedItem) {
            $beneficiary = $distributedItem->getBeneficiary();
            $assistance = $distributedItem->getAssistance();
            $commodity = $distributedItem->getCommodity();
            $datetime = $distributedItem->getDateDistribution();
            $fieldOfficerEmail = $distributedItem->getFieldOfficer()?->getEmail();
            $primaryNationalId = $beneficiary->getPerson()->getPrimaryNationalId();
            $secondaryNationalId = $beneficiary->getPerson()->getSecondaryNationalId();
            $tertiaryNationalId = $beneficiary->getPerson()->getTertiaryNationalId();

            $exportableTable [] = [
                $this->translator->trans('Beneficiary ID') => $beneficiary->getId(),
                $this->translator->trans('Beneficiary Type') => $beneficiary->isHead() ? $this->translator->trans('Household') : $this->translator->trans('Individual'),
                $this->translator->trans('Beneficiary First Name (local)') => $beneficiary->getPerson()->getLocalGivenName(),
                $this->translator->trans('Beneficiary Family Name (local)') => $beneficiary->getPerson()->getLocalFamilyName(),
                $this->translator->trans('Primary ID Type') => $primaryNationalId ? $this->translator->trans($primaryNationalId->getIdType()) : $this->translator->trans('N/A'),
                $this->translator->trans('Primary ID Number') => $primaryNationalId ? $primaryNationalId->getIdNumber() : $this->translator->trans('N/A'),
                $this->translator->trans('Secondary ID Type') => $secondaryNationalId ? $this->translator->trans($secondaryNationalId->getIdType()) : $this->translator->trans('N/A'),
                $this->translator->trans('Secondary ID Number') => $secondaryNationalId ? $secondaryNationalId->getIdNumber() : $this->translator->trans('N/A'),
                $this->translator->trans('Tertiary ID Type') => $tertiaryNationalId ? $this->translator->trans($tertiaryNationalId->getIdType()) : $this->translator->trans('N/A'),
                $this->translator->trans('Tertiary ID Number') => $tertiaryNationalId ? $tertiaryNationalId->getIdNumber() : $this->translator->trans('N/A'),
                $this->translator->trans('Phone') => $beneficiary->getPerson()->getFirstPhoneWithPrefix() ?? $this->translator->trans('N/A'),
                $this->translator->trans('Distribution Name') => $assistance->getName(),
                $this->translator->trans('Round') => $assistance->getRound() ?? $this->translator->trans('N/A'),
                $this->translator->trans('Location') => $distributedItem->getLocation()->getFullPathNames("\n"),
                $this->translator->trans('Date of Distribution') => $datetime ? $dateFormatter->format($datetime) : $this->translator->trans('N/A'),
                $this->translator->trans('Commodity Type') => $distributedItem->getModalityType(),
                $this->translator->trans('Carrier No.') => $distributedItem->getCarrierNumber() ?? $this->translator->trans('N/A'),
                $this->translator->trans('Quantity') => $commodity->getValue(),
                $this->translator->trans('Distributed') => $distributedItem->getAmount(),
                $this->translator->trans('Spent') => $distributedItem->getSpent(),
                $this->translator->trans('Unit') => $commodity->getUnit(),
                $this->translator->trans('Field Officer Email') => $fieldOfficerEmail ?? $this->translator->trans('N/A'),
            ];
        }

        return $exportableTable;
    }
}
