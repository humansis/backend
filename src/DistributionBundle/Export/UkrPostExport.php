<?php

namespace DistributionBundle\Export;

use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Utils\ExcelColumnsGenerator;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Entity\DistributionData;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style;

class UkrPostExport
{
    private const HEADERS = [
        ['NPP', '0', 4.25],
        ['NPS', '0', 5.00],
        ['LASTNAME', 'ФІЛІЯ КОМПАНІЇ\nЛЮДИНА В БІДІ', 14.50],
        ['FIRSTNAME', '', 12.00],
        ['MIDDLENAME', '', 15.50],
        ['ZIP', '01034', 6.00],
        ['CITY', 'КИЇВ', 13.25],
        ['COUNTRY', 'UA', 4.00],
        ['REGION', 'КИЇВ', 9.75],
        ['DISTRICT', 'КИЇВ', 13.00],
        ['STREET', 'ПРОРІЗНА', 14.25],
        ['BUILDING', '4', 10.75],
        ['APT', '21', 7.50],
        ['AMOUNT', '0,00', 10.25],
        ['LUI', '0,00', 13.75],
        ['PHONE', '0507787276', 23.00],
    ];

    private const HEADER_STYLE = [
        'font' => [
            'bold' => true,
            'size' => 9,
            'name' => 'Sans Serif',
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Style\Border::BORDER_THIN,
                'color' => [
                    'argb' => '000000',
                ],
            ],
        ],
        'fill' => [
            'fillType' => Style\Fill::FILL_SOLID,
            'startColor' => [
                'argb' => 'FAC090',
            ],
        ],
    ];

    private const TITLE_STYLE = [
        'font' => [
            'bold' => true,
            'size' => 12,
            'name' => 'Serif',
        ],
    ];

    public function export(DistributionData $distribution)
    {
        if ('UKR' !== $distribution->getProject()->getIso3()) {
            throw new \InvalidArgumentException('Distribution is not exportable to UKR post sheet.');
        }

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setCellValue('C2', 'Договор № 11/1070');
        $worksheet->setCellValue('C3', 'від «07» жовтня 2019 року');
        $worksheet->setCellValue('J2', 'to the Agreement No. 11/1070');
        $worksheet->setCellValue('J3', 'dated «07» October 2019');
        $worksheet->setCellValue('A5', 'СПИСОК');
        $worksheet->setCellValue('A6', 'згрупованих поштових переказів електронних ф.103-1/№34/05.05.2020');
        $worksheet->setCellValue('J5', 'LIST');
        $worksheet->setCellValue('J6', 'of grouped electronic postal transfers f.103-1/№34/05.05.2020');

        $dim = [];

        $col = 'A';
        $generator = new ExcelColumnsGenerator();
        foreach (self::HEADERS as list($title, $example, $width)) {
            $dim[$title] = $col = $generator->getNext();
            $worksheet->getColumnDimension($col)->setWidth($width);
            $worksheet->setCellValue($col.'8', $title);
            $worksheet->setCellValue($col.'9', $example);
        }

        $worksheet->getStyle('A1:P7')->applyFromArray(self::TITLE_STYLE);
        $worksheet->getStyle('A8:'.$col.'9')->applyFromArray(self::HEADER_STYLE);
        $worksheet->getStyle('A9:'.$col.'9')->getFont()->setSize(11);
        $worksheet->mergeCells('A5:G5');
        $worksheet->mergeCells('J5:O5');

        $row = 10;
        foreach ($distribution->getDistributionBeneficiaries() as $distributionBeneficiary) {
            foreach (self::HEADERS as list($title,)) {
                $coord = $dim[$title].$row;
                $value = $this->getValue($distributionBeneficiary, $title);
                $worksheet->setCellValue($coord, $value);
            }
            ++$row;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('ukrposta.xlsx');

        return 'urkposta.xlsx';
    }

    private function getValue(DistributionBeneficiary $distributionBeneficiary, string $title)
    {
        switch ($title) {
            case 'LASTNAME':
                return $distributionBeneficiary->getBeneficiary()->getPerson()->getLocalFamilyName();
            case 'FIRSTNAME':
                return $distributionBeneficiary->getBeneficiary()->getPerson()->getLocalGivenName();
            case 'MIDDLENAME':
                return $distributionBeneficiary->getBeneficiary()->getPerson()->getLocalParentsName();
            case 'ZIP':
                /** @var HouseholdLocation[] $locations */
                $locations = $distributionBeneficiary->getBeneficiary()->getHousehold()->getHouseholdLocations();
                return reset($locations)->getAddress()->getPostcode();
            case 'CITY':
                /** @var HouseholdLocation[] $locations */
                $locations = $distributionBeneficiary->getBeneficiary()->getHousehold()->getHouseholdLocations();
                return reset($locations)->getAddress()->getLocation()->getAdm4Name();
            case 'COUNTRY':
                return 'UA';
            case 'REGION':
                /** @var HouseholdLocation[] $locations */
                $locations = $distributionBeneficiary->getBeneficiary()->getHousehold()->getHouseholdLocations();
                return reset($locations)->getAddress()->getLocation()->getAdm1Name();
            case 'DISTRICT':
                /** @var HouseholdLocation[] $locations */
                $locations = $distributionBeneficiary->getBeneficiary()->getHousehold()->getHouseholdLocations();
                return reset($locations)->getAddress()->getLocation()->getAdm2();
            case 'STREET':
                /** @var HouseholdLocation[] $locations */
                $locations = $distributionBeneficiary->getBeneficiary()->getHousehold()->getHouseholdLocations();
                return reset($locations)->getAddress()->getStreet();
            case 'BUILDING':
                /** @var HouseholdLocation[] $locations */
                $locations = $distributionBeneficiary->getBeneficiary()->getHousehold()->getHouseholdLocations();
                return reset($locations)->getAddress()->getNumber();
            case 'AMOUNT':
                $sum = 0.0;
                foreach ($distributionBeneficiary->getTransactions() as $transaction) {
                    /** @var \TransactionBundle\Entity\Transaction $transaction */
                    $sum .= $transaction->getAmountSent();
                }
                return $sum;
            case 'LUI':
                $sum = 0.0;
                foreach ($distributionBeneficiary->getTransactions() as $transaction) {
                    /** @var \TransactionBundle\Entity\Transaction $transaction */
                    $sum .= $transaction->getAmountSent();
                }
                return ($sum < 2000) ? $sum *0.03 : ($sum < 3000) ? $sum * 0.01 : $sum * 0.008;
            case 'PHONE':
                $phones = $distributionBeneficiary->getBeneficiary()->getPerson()->getPhones();
                return reset($phones)->getPrefix().reset($phones)->getNumber();
            default:
                return null;
        }
    }
}
