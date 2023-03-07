<?php

namespace Utils;

use Symfony\Contracts\Translation\TranslatorInterface;

class ReliefBeneficiaryTransformData
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Returns an array representation of relief beneficiaries in order to prepare the export
     */
    public function transformData(array $packages): array
    {
        $exportableTable = [];
        foreach ($packages as $relief) {
            $beneficiary = $relief->getAssistanceBeneficiary()->getBeneficiary();
            $commodityNames = $relief->getModalityType();

            $commonFields = $beneficiary->getCommonExportFields();

            $exportableTable[] = array_merge($commonFields, [
                $this->translator->trans("Commodity") => $commodityNames,
                $this->translator->trans("To Distribute") => (float)$relief->getAmountToDistribute(),
                $this->translator->trans("Spent") => (float)$relief->getAmountSpent() ?? 0,
                $this->translator->trans("Unit") => $relief->getUnit(),
                $this->translator->trans("Distributed At") => $relief->getLastModifiedAt(),
                $this->translator->trans("Notes Distribution") => $relief->getNotes(),
                $this->translator->trans("Removed") => $relief->getAssistanceBeneficiary()->getRemoved() ? 'Yes' : 'No',
                $this->translator->trans("Justification for adding/removing") => $relief->getAssistanceBeneficiary(
                )->getJustification(),
            ]);
        }

        return $exportableTable;
    }
}
