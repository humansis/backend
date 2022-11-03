<?php

declare(strict_types=1);

namespace Export;

use Entity\Beneficiary;
use Entity\NationalId;
use Entity\Phone;
use Entity\Assistance;
use Component\Country\Countries;
use Component\Country\Country;
use Enum\NationalIdType;
use InputType\DistributedItemFilterInputType;
use IntlDateFormatter;
use InvalidArgumentException;
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

    public function export(string $countryIso3, string $filetype, DistributedItemFilterInputType $filter)
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

    private function build(Worksheet $worksheet, Country $country, DistributedItemFilterInputType $filter)
    {
        $worksheet->getColumnDimension('A')->setWidth(16.852);
        $worksheet->getColumnDimension('B')->setWidth(14.423);
        $worksheet->getColumnDimension('C')->setWidth(16.614);
        $worksheet->getColumnDimension('D')->setWidth(18.136);
        $worksheet->getColumnDimension('E')->setWidth(13.565);
        $worksheet->getColumnDimension('F')->setWidth(13.565);
        $worksheet->getColumnDimension('G')->setWidth(12.565);
        $worksheet->getColumnDimension('H')->setWidth(12.565);
        $worksheet->getColumnDimension('I')->setWidth(14.853);
        $worksheet->getColumnDimension('J')->setWidth(14.853);
        $worksheet->getColumnDimension('K')->setWidth(19.136);
        $worksheet->getColumnDimension('L')->setWidth(14.423);
        $worksheet->getColumnDimension('M')->setWidth(14.423);
        $worksheet->getColumnDimension('N')->setWidth(14.423);
        $worksheet->getColumnDimension('O')->setWidth(14.423);
        $worksheet->getColumnDimension('P')->setWidth(08.837);
        $worksheet->getColumnDimension('Q')->setWidth(28.997);
        $worksheet->getRowDimension(1)->setRowHeight(28.705);
        $worksheet->setRightToLeft('right-to-left' === Misc::getCharacterOrder($this->translator->getLocale()));
        $worksheet->getStyle('A1:Q1')->applyFromArray([
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
        $worksheet->setCellValue('F1', $this->translator->trans('Phone'));
        $worksheet->setCellValue('G1', $this->translator->trans('Distribution Name'));
        $worksheet->setCellValue('H1', $this->translator->trans('Round'));
        $worksheet->setCellValue('I1', $this->translator->trans('Location'));
        $worksheet->setCellValue('J1', $this->translator->trans('Date of Distribution'));
        $worksheet->setCellValue('K1', $this->translator->trans('Commodity Type'));
        $worksheet->setCellValue('L1', $this->translator->trans('Carrier No.'));
        $worksheet->setCellValue('M1', $this->translator->trans('Quantity'));
        $worksheet->setCellValue('N1', $this->translator->trans('Distributed'));
        $worksheet->setCellValue('O1', $this->translator->trans('Spent'));
        $worksheet->setCellValue('P1', $this->translator->trans('Unit'));
        $worksheet->setCellValue('Q1', $this->translator->trans('Field Officer Email'));

        $i = 1;
        foreach ($this->repository->findByParams($country->getIso3(), $filter) as $distributedItem) {
            $beneficiary = $distributedItem->getBeneficiary();
            $assistance = $distributedItem->getAssistance();
            $commodity = $distributedItem->getCommodity();
            $datetime = $distributedItem->getDateDistribution();
            $fieldOfficerEmail = $distributedItem->getFieldOfficer() ? $distributedItem->getFieldOfficer()->getEmail(
            ) : null;

            $i++;
            $worksheet->setCellValue('A' . $i, $beneficiary->getId());
            $worksheet->setCellValue(
                'B' . $i,
                $beneficiary->isHead() ? $this->translator->trans('Household') : $this->translator->trans('Individual')
            );
            $worksheet->setCellValue('C' . $i, $beneficiary->getLocalGivenName());
            $worksheet->setCellValue('D' . $i, $beneficiary->getLocalFamilyName());
            $worksheet->setCellValue('E' . $i, self::nationalId($beneficiary) ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('F' . $i, self::phone($beneficiary) ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('G' . $i, $assistance->getName());
            $worksheet->setCellValue('H' . $i, $assistance->getRound() ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('I' . $i, $distributedItem->getLocation()->getFullPathNames("\n"));
            $worksheet->setCellValue(
                'J' . $i,
                $datetime ? $dateFormatter->format($datetime) : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue('K' . $i, $distributedItem->getModalityType());
            $worksheet->setCellValue('L' . $i, $distributedItem->getCarrierNumber() ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('M' . $i, $commodity->getValue());
            $worksheet->setCellValue('N' . $i, $distributedItem->getAmount());
            $worksheet->setCellValue('O' . $i, $distributedItem->getSpent());
            $worksheet->setCellValue('P' . $i, $commodity->getUnit());
            $worksheet->setCellValue('Q' . $i, $fieldOfficerEmail ?? $this->translator->trans('N/A'));
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

    private static function nationalId(Beneficiary $beneficiary): ?string
    {
        /** @var NationalId $nationalId */
        foreach ($beneficiary->getPerson()->getNationalIds() as $nationalId) {
            if (NationalIdType::NATIONAL_ID === $nationalId->getIdType()) {
                return $nationalId->getIdNumber();
            }
        }

        return null;
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
