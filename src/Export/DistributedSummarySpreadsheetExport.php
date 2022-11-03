<?php

declare(strict_types=1);

namespace Export;

use Component\Country\Countries;
use Component\Country\Country;
use InputType\DistributedItemFilterInputType;
use IntlDateFormatter;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Punic\Misc;
use Repository\DistributedItemRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Translation\TranslatorInterface;

class DistributedSummarySpreadsheetExport
{
    public function __construct(private readonly TranslatorInterface $translator, private readonly Countries $countries, private readonly DistributedItemRepository $repository)
    {
    }

    /**
     * @param string $countryIso3
     * @param string $filetype
     * @param DistributedItemFilterInputType $filter
     * @return string
     * @throws Exception
     */
    public function export(string $countryIso3, string $filetype, DistributedItemFilterInputType $filter): string
    {
        $country = $this->countries->getCountry($countryIso3);
        if (!$country) {
            throw new InvalidArgumentException('Invalid country ' . $countryIso3);
        }

        if (!in_array($filetype, ['ods', 'xlsx', 'csv'], true)) {
            throw new InvalidArgumentException(
                'Invalid file type. Expected one of ods, xlsx, csv. ' . $filetype . ' given.'
            );
        }

        $filename = sys_get_temp_dir() . '/summary.' . $filetype;

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $this->build($worksheet, $country, $filter);

        $writer = IOFactory::createWriter($spreadsheet, ucfirst($filetype));
        $writer->save($filename);

        return $filename;
    }

    private function build(Worksheet $worksheet, Country $country, DistributedItemFilterInputType $filter): void
    {
        $worksheet->getColumnDimension('A')->setWidth(16.852);
        $worksheet->getColumnDimension('B')->setWidth(14.423);
        $worksheet->getColumnDimension('C')->setWidth(16.614);
        $worksheet->getColumnDimension('D')->setWidth(18.136);
        $worksheet->getColumnDimension('E')->setWidth(13.565);
        $worksheet->getColumnDimension('F')->setWidth(13.565);
        $worksheet->getColumnDimension('G')->setWidth(13.565);
        $worksheet->getColumnDimension('H')->setWidth(13.565);
        $worksheet->getColumnDimension('I')->setWidth(13.565);
        $worksheet->getColumnDimension('J')->setWidth(13.565);
        $worksheet->getColumnDimension('K')->setWidth(13.565);
        $worksheet->getColumnDimension('L')->setWidth(12.565);
        $worksheet->getColumnDimension('M')->setWidth(12.565);
        $worksheet->getColumnDimension('N')->setWidth(14.853);
        $worksheet->getColumnDimension('O')->setWidth(14.853);
        $worksheet->getColumnDimension('P')->setWidth(19.136);
        $worksheet->getColumnDimension('Q')->setWidth(14.423);
        $worksheet->getColumnDimension('R')->setWidth(14.423);
        $worksheet->getColumnDimension('S')->setWidth(14.423);
        $worksheet->getColumnDimension('T')->setWidth(14.423);
        $worksheet->getColumnDimension('U')->setWidth(08.837);
        $worksheet->getColumnDimension('V')->setWidth(28.997);
        $worksheet->getRowDimension(1)->setRowHeight(28.705);
        $worksheet->setRightToLeft('right-to-left' === Misc::getCharacterOrder($this->translator->getLocale()));
        $worksheet->getStyle('A1:V1')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'font' => [
                'size' => 11,
                'bold' => true,
            ],
        ]);

        $dateFormatter = new IntlDateFormatter(
            $this->translator->getLocale(),
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE
        );

        $worksheet->setCellValue('A1', $this->translator->trans('Beneficiary ID'));
        $worksheet->setCellValue('B1', $this->translator->trans('Beneficiary Type'));
        $worksheet->setCellValue('C1', $this->translator->trans('Beneficiary First Name (local)'));
        $worksheet->setCellValue('D1', $this->translator->trans('Beneficiary Family Name (local)'));
        $worksheet->setCellValue('E1', 'Primary ID Type');
        $worksheet->setCellValue('F1', 'Primary ID Number');
        $worksheet->setCellValue('G1', 'Secondary ID Type');
        $worksheet->setCellValue('H1', 'Secondary ID Number');
        $worksheet->setCellValue('I1', 'Tertiary ID Type');
        $worksheet->setCellValue('J1', 'Tertiary ID Number');
        $worksheet->setCellValue('K1', $this->translator->trans('Phone'));
        $worksheet->setCellValue('L1', $this->translator->trans('Distribution Name'));
        $worksheet->setCellValue('M1', $this->translator->trans('Round'));
        $worksheet->setCellValue('N1', $this->translator->trans('Location'));
        $worksheet->setCellValue('O1', $this->translator->trans('Date of Distribution'));
        $worksheet->setCellValue('P1', $this->translator->trans('Commodity Type'));
        $worksheet->setCellValue('Q1', $this->translator->trans('Carrier No.'));
        $worksheet->setCellValue('R1', $this->translator->trans('Quantity'));
        $worksheet->setCellValue('S1', $this->translator->trans('Distributed'));
        $worksheet->setCellValue('T1', $this->translator->trans('Spent'));
        $worksheet->setCellValue('U1', $this->translator->trans('Unit'));
        $worksheet->setCellValue('V1', $this->translator->trans('Field Officer Email'));

        $i = 1;
        foreach ($this->repository->findByParams($country->getIso3(), $filter) as $distributedItem) {
            $beneficiary = $distributedItem->getBeneficiary();
            $assistance = $distributedItem->getAssistance();
            $commodity = $distributedItem->getCommodity();
            $datetime = $distributedItem->getDateDistribution();
            $fieldOfficerEmail = $distributedItem->getFieldOfficer() ? $distributedItem->getFieldOfficer()->getEmail(
            ) : null;
            $primaryNationalId = $beneficiary->getPerson()->getPrimaryNationalId();
            $secondaryNationalId = $beneficiary->getPerson()->getSecondaryNationalId();
            $tertiaryNationalId = $beneficiary->getPerson()->getTertiaryNationalId();

            $i++;
            $worksheet->setCellValue('A' . $i, $beneficiary->getId());
            $worksheet->setCellValue(
                'B' . $i,
                $beneficiary->isHead() ? $this->translator->trans('Household') : $this->translator->trans('Individual')
            );
            $worksheet->setCellValue('C' . $i, $beneficiary->getPerson()->getLocalGivenName());
            $worksheet->setCellValue('D' . $i, $beneficiary->getPerson()->getLocalFamilyName());
            $worksheet->setCellValue(
                'E' . $i,
                $primaryNationalId ? $this->translator->trans(
                    $primaryNationalId->getIdType()
                ) : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue(
                'F' . $i,
                $primaryNationalId ? $primaryNationalId->getIdNumber() : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue(
                'G' . $i,
                $secondaryNationalId ? $this->translator->trans(
                    $secondaryNationalId->getIdType()
                ) : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue(
                'H' . $i,
                $secondaryNationalId ? $secondaryNationalId->getIdNumber() : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue(
                'I' . $i,
                $tertiaryNationalId ? $this->translator->trans(
                    $tertiaryNationalId->getIdType()
                ) : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue(
                'J' . $i,
                $tertiaryNationalId ? $tertiaryNationalId->getIdNumber() : $this->translator->trans('N/A')
            );

            $worksheet->setCellValue(
                'K' . $i,
                $beneficiary->getPerson()->getFirstPhoneWithPrefix() ?? $this->translator->trans('N/A')
            );
            $worksheet->setCellValue('L' . $i, $assistance->getName());
            $worksheet->setCellValue('M' . $i, $assistance->getRound() ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('N' . $i, $distributedItem->getLocation()->getFullPathNames("\n"));
            $worksheet->setCellValue(
                'O' . $i,
                $datetime ? $dateFormatter->format($datetime) : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue('P' . $i, $distributedItem->getModalityType());
            $worksheet->setCellValue('Q' . $i, $distributedItem->getCarrierNumber() ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('R' . $i, $commodity->getValue());
            $worksheet->setCellValue('S' . $i, $distributedItem->getAmount());
            $worksheet->setCellValue('T' . $i, $distributedItem->getSpent());
            $worksheet->setCellValue('U' . $i, $commodity->getUnit());
            $worksheet->setCellValue('V' . $i, $fieldOfficerEmail ?? $this->translator->trans('N/A'));
        }
        $worksheet->getStyle('I2:I' . $i)->applyFromArray([
            'alignment' => [
                'wrapText' => true,
            ],
        ]);
    }
}
