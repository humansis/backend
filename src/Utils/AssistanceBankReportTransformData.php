<?php

declare(strict_types=1);

namespace Utils;

use Symfony\Contracts\Translation\TranslatorInterface;

class AssistanceBankReportTransformData
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Returns an array representation of assistance bank report in order to prepare the export
     *
     * @param $distributions
     *
     * @return array
     */
    public function transformData($distributions): array
    {
        $exportableTable = [];

        foreach ($distributions as $distribution) {
            $exportableTable [] = [
                $this->translator->trans('Ordinal number') => $distribution['distributionId'],
                $this->translator->trans('Recipient’s surname (Local family name)') => $distribution['localFamilyName'],
                $this->translator->trans('Recipient’s name (Local given name)') => $distribution['localGivenName'],
                $this->translator->trans('Recipient’s patronymic (Local parent’s name)') => $distribution['localParentsName'],
                $this->translator->trans('Recipient’s RNTRC (Tax Number)') => $distribution['taxNumber'],
                $this->translator->trans('Document type') => $distribution['idType'],
                $this->translator->trans('Document number') => $distribution['idNumber'],
                $this->translator->trans('Remittance purpose') => 'Благодійна допомога',
                $this->translator->trans('Remittance amount') => $distribution['amountToDistribute'],
                $this->translator->trans('Remittance currency') => $distribution['currency'],
                $this->translator->trans('Recipient’s mobile telephone number') => $distribution['phoneNumber']
            ];
        }
        return $exportableTable;
    }
}
