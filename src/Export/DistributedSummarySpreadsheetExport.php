<?php

declare(strict_types=1);

namespace Export;

use Entity\Beneficiary;
use Entity\Phone;
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
use Symfony\Component\Translation\TranslatorInterface;

class DistributedSummarySpreadsheetExport
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var Countries */
    private $countries;

    /** @var DistributedItemRepository */
    private $repository;

    public function __construct(
        TranslatorInterface $translator,
        Countries $countries,
        DistributedItemRepository $repository
    ) {
        $this->translator = $translator;
        $this->countries = $countries;
        $this->repository = $repository;
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
        $worksheet->getColumnDimension('H')->setWidth(12.565);
        $worksheet->getColumnDimension('I')->setWidth(12.565);
        $worksheet->getColumnDimension('J')->setWidth(14.853);
        $worksheet->getColumnDimension('K')->setWidth(14.853);
        $worksheet->getColumnDimension('L')->setWidth(19.136);
        $worksheet->getColumnDimension('M')->setWidth(14.423);
        $worksheet->getColumnDimension('N')->setWidth(14.423);
        $worksheet->getColumnDimension('O')->setWidth(14.423);
        $worksheet->getColumnDimension('P')->setWidth(14.423);
        $worksheet->getColumnDimension('Q')->setWidth(08.837);
        $worksheet->getColumnDimension('R')->setWidth(28.997);
        $worksheet->getRowDimension(1)->setRowHeight(28.705);
        $worksheet->setRightToLeft('right-to-left' === Misc::getCharacterOrder($this->translator->getLocale()));
        $worksheet->getStyle('A1:R1')->applyFromArray([
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
        $worksheet->setCellValue('E1', $this->translator->trans('ID Number'));
        $worksheet->setCellValue('F1', $this->translator->trans('ID Number'));
        $worksheet->setCellValue('G1', $this->translator->trans('Phone'));
        $worksheet->setCellValue('H1', $this->translator->trans('Distribution Name'));
        $worksheet->setCellValue('I1', $this->translator->trans('Round'));
        $worksheet->setCellValue('J1', $this->translator->trans('Location'));
        $worksheet->setCellValue('K1', $this->translator->trans('Date of Distribution'));
        $worksheet->setCellValue('L1', $this->translator->trans('Commodity Type'));
        $worksheet->setCellValue('M1', $this->translator->trans('Carrier No.'));
        $worksheet->setCellValue('N1', $this->translator->trans('Quantity'));
        $worksheet->setCellValue('O1', $this->translator->trans('Distributed'));
        $worksheet->setCellValue('P1', $this->translator->trans('Spent'));
        $worksheet->setCellValue('Q1', $this->translator->trans('Unit'));
        $worksheet->setCellValue('R1', $this->translator->trans('Field Officer Email'));

        $i = 1;
        foreach ($this->repository->findByParams($country->getIso3(), $filter) as $distributedItem) {
            $beneficiary = $distributedItem->getBeneficiary();
            $assistance = $distributedItem->getAssistance();
            $commodity = $distributedItem->getCommodity();
            $datetime = $distributedItem->getDateDistribution();
            $fieldOfficerEmail = $distributedItem->getFieldOfficer() ? $distributedItem->getFieldOfficer()->getEmail(
            ) : null;
            $primaryNationalId = $beneficiary->getPerson()->getPrimaryNationalId();

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
            $worksheet->setCellValue('F' . $i, $primaryNationalId ? $primaryNationalId->getIdNumber() : 'N/A');
            $worksheet->setCellValue('G' . $i, self::phone($beneficiary) ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('H' . $i, $assistance->getName());
            $worksheet->setCellValue('I' . $i, $assistance->getRound() ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('J' . $i, $distributedItem->getLocation()->getFullPathNames("\n"));
            $worksheet->setCellValue(
                'K' . $i,
                $datetime ? $dateFormatter->format($datetime) : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue('L' . $i, $distributedItem->getModalityType());
            $worksheet->setCellValue('M' . $i, $distributedItem->getCarrierNumber() ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('N' . $i, $commodity->getValue());
            $worksheet->setCellValue('O' . $i, $distributedItem->getAmount());
            $worksheet->setCellValue('P' . $i, $distributedItem->getSpent());
            $worksheet->setCellValue('Q' . $i, $commodity->getUnit());
            $worksheet->setCellValue('R' . $i, $fieldOfficerEmail ?? $this->translator->trans('N/A'));
        }
        $worksheet->getStyle('I2:I' . $i)->applyFromArray([
            'alignment' => [
                'wrapText' => true,
            ],
        ]);
    }

    private static function phone(Beneficiary $beneficiary): ?string
    {
        /** @var Phone $phone */
        foreach ($beneficiary->getPerson()->getPhones() as $phone) {
            if (!$phone->getProxy()) {
                return $phone->getPrefix() . ' ' . $phone->getNumber();
            }
        }

        return null;
    }
}
