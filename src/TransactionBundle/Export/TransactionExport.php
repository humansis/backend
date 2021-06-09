<?php

declare(strict_types=1);

namespace TransactionBundle\Export;

use DistributionBundle\Entity\Assistance;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * @deprecated legacy raw export for mobile money export
 */
class TransactionExport
{
    public function export(Assistance $assistance)
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        self::formatCells($worksheet);
        self::buildHeader($worksheet, $assistance);
        self::buildBody($worksheet);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('transaction.xlsx');

        return 'transaction.xlsx';
    }

    private static function formatCells(Worksheet $worksheet)
    {
        $worksheet->getColumnDimension('A')->setWidth(8.141);
        $worksheet->getColumnDimension('B')->setWidth(155.140);
        $worksheet->getColumnDimension('C')->setWidth(8.855);
        $worksheet->getColumnDimension('D')->setWidth(10.142);
        $worksheet->getColumnDimension('E')->setWidth(11.425);
        $worksheet->getColumnDimension('F')->setWidth(12.567);
        $worksheet->getColumnDimension('G')->setWidth(10.283);
        $worksheet->getColumnDimension('H')->setWidth(13.142);
        $worksheet->getColumnDimension('I')->setWidth(12.425);
        $worksheet->getColumnDimension('J')->setWidth(10.996);
        $worksheet->getColumnDimension('K')->setWidth(02.429);

        $worksheet->getStyle('A1:K10000')->getFont()
            ->setBold(false)
            ->setSize(12)
            ->setName('Arial');
        $worksheet->getStyle('A1:K10000')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private static function buildHeader(Worksheet $worksheet, Assistance  $assistance)
    {
        $worksheet->getRowDimension('1')->setRowHeight(16.50);
        $worksheet->getRowDimension('2')->setRowHeight(64.35);
        $worksheet->getRowDimension('3')->setRowHeight(0);
        $worksheet->getRowDimension('4')->setRowHeight(31.5);
        $worksheet->getRowDimension('5')->setRowHeight(0);
        $worksheet->getRowDimension('6')->setRowHeight(31.5);
        $worksheet->getRowDimension('7')->setRowHeight(0);
        $worksheet->getRowDimension('8')->setRowHeight(0);
        $worksheet->getRowDimension('13')->setRowHeight(0);

        $worksheet->setCellValue('B2', 'DISTRIBUTION LIST');
        $worksheet->mergeCells('B2:D2');
        $worksheet->getStyle('B2')->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THICK);
        $worksheet->getStyle('B2')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $worksheet->getStyle('B2')->getFont()
            ->setBold(true);

        $worksheet->getStyle('B3:K12')->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_MEDIUM);
        $worksheet->getStyle('B3:K12')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $worksheet->setCellValue('C2', 'Distribution No.');
        $worksheet->setCellValue('C3', 'Distribution No.');
        $worksheet->setCellValue('D2', $assistance->getId());
        $worksheet->mergeCells('D2:D3');
        $worksheet->getStyle('C2')->getFont()
            ->setBold(true);
        $worksheet->getStyle('C2')->getFont()
            ->setItalic(true);

        $worksheet->setCellValue('E2', "Location");
        $worksheet->setCellValue('E3', "Location");
        $worksheet->setCellValue('F2', $assistance->getLocation()->getLocationName());
        $worksheet->mergeCells('F2:F3');
        $worksheet->getStyle('E2')->getFont()
            ->setBold(true);
        $worksheet->getStyle('E3')->getFont()
            ->setItalic(true);

        $donors = [];
        foreach ($assistance->getProject()->getDonors() as $donor) {
            $donors[] = $donor->getShortname();
        }

        $worksheet->setCellValue('G2', 'Project & Donor');
        $worksheet->setCellValue('G3', 'Project & Donor');
        $worksheet->setCellValue('H2', $assistance->getProject()->getName().' & '.self::getDonors($assistance));
        $worksheet->mergeCells('H2:H3');
        $worksheet->getStyle('G2')->getFont()
            ->setBold(true);
        $worksheet->getStyle('G2')->getFont()
            ->setItalic(true);

        $worksheet->setCellValue('I2', 'Date');
        $worksheet->setCellValue('I3', 'Date');
        $worksheet->setCellValue('J2', $assistance->getDateDistribution()->format('Y-m-d'));
        $worksheet->mergeCells('J2:J3');
        $worksheet->getStyle('I2')->getFont()
            ->setBold(true);
        $worksheet->getStyle('I2')->getFont()
            ->setItalic(true);

        $worksheet->setCellValue('C7', 'Distributed item(s)');
        $worksheet->setCellValue('C8', 'Distributed item(s)');
        $worksheet->mergeCells('D7:D8');
        $worksheet->getStyle('C7')->getFont()
            ->setBold(true);
        $worksheet->getStyle('C8')->getFont()
            ->setItalic(true);

        $worksheet->setCellValue('E7', "'Distributed item(s)");
        $worksheet->setCellValue('E8', "'Distributed item(s)");
        $worksheet->mergeCells('F7:F8');
        $worksheet->getStyle('E7')->getFont()
            ->setBold(true);
        $worksheet->getStyle('E8')->getFont()
            ->setItalic(true);

        $worksheet->setCellValue('G7', 'Distributed item(s)');
        $worksheet->setCellValue('G8', 'Distributed item(s)');
        $worksheet->mergeCells('H7:H8');
        $worksheet->getStyle('G7')->getFont()
            ->setBold(true);
        $worksheet->getStyle('G8')->getFont()
            ->setItalic(true);

        $worksheet->setCellValue('I7', 'Round');
        $worksheet->setCellValue('I8', 'Round');
        $worksheet->mergeCells('J7:J8');
        $worksheet->getStyle('I7')->getFont()
            ->setBold(true);
        $worksheet->getStyle('I8')->getFont()
            ->setItalic(true);

        $worksheet->setCellValue('C10', "Distributed by: \n(name, position, signature)");
        $worksheet->setCellValue('C11', "Distributed by: \n(name, position, signature)");
        $worksheet->mergeCells('D10:D11');
        $worksheet->getStyle('C10')->getFont()
            ->setBold(true);
        $worksheet->getStyle('C11')->getFont()
            ->setItalic(true);

        $worksheet->setCellValue('G10', "Approved by: \n(name, position, signature)");
        $worksheet->setCellValue('G11', "Approved by: \n(name, position, signature)");
        $worksheet->mergeCells('H10:H11');
        $worksheet->getStyle('G10')->getFont()
            ->setBold(true);
        $worksheet->getStyle('G11')->getFont()
            ->setItalic(true);

        $worksheet->setCellValue('B14',
            'The below listed persons confirm by their signature of this distribution list that they obtained and accepted the donation of the below specified items from People in Need.');
        $worksheet->setCellValue('B15',
            'The below listed persons confirm by their signature of this distribution list that they obtained and accepted the donation of the below specified items from People in Need.');
        $worksheet->mergeCells('B14:K14');
        $worksheet->mergeCells('B15:K15');
    }

    private static function buildBody(Worksheet $worksheet)
    {
        $worksheet->mergeCells('B14:C14');
        $worksheet->mergeCells('H14:I14');
        $worksheet->getStyle('B14:J14')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('C5E0B4'));
        $worksheet->getStyle('B14:J14')->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyle('B14:J14')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $worksheet->setCellValue('B14', 'No.');
        $worksheet->setCellValue('B14', 'First Name');
        $worksheet->setCellValue('B14', 'Second Name');
        $worksheet->setCellValue('B14', 'ID No.');
        $worksheet->setCellValue('B14', 'Phone No.');
        $worksheet->setCellValue('B14', 'Proxy First Name');
        $worksheet->setCellValue('B14', 'Proxy Second Name');
        $worksheet->setCellValue('B14', 'Proxy ID No.');
        $worksheet->setCellValue('B14', 'Distributed Item(s), Unit, Amount per beneficiary');
        $worksheet->setCellValue('B14', 'Signature');

        $worksheet->setCellValue('B14', 'No.');
        $worksheet->setCellValue('B14', 'First Name');
        $worksheet->setCellValue('B14', 'Second Name');
        $worksheet->setCellValue('B14', 'ID No.');
        $worksheet->setCellValue('B14', 'Phone No.');
        $worksheet->setCellValue('B14', 'Proxy First Name');
        $worksheet->setCellValue('B14', 'Proxy Second Name');
        $worksheet->setCellValue('B14', 'Proxy ID No.');
        $worksheet->setCellValue('B14', 'Distributed Item(s), Unit, Amount per beneficiary');
        $worksheet->setCellValue('B14', 'Signature');

        $worksheet->setCellValue('B14', 'No.');
        $worksheet->setCellValue('B14', 'First Name');
        $worksheet->setCellValue('B14', 'Second Name');
        $worksheet->setCellValue('B14', 'ID No.');
        $worksheet->setCellValue('B14', 'Phone No.');
        $worksheet->setCellValue('B14', 'Proxy First Name');
        $worksheet->setCellValue('B14', 'Proxy Second Name');
        $worksheet->setCellValue('B14', 'Proxy ID No.');
        $worksheet->setCellValue('B14', 'Distributed Item(s), Unit, Amount per beneficiary');
        $worksheet->setCellValue('B14', 'Signature');
    }

    private static function getDonors(Assistance $assistance): string
    {
        $donors = [];
        foreach ($assistance->getProject()->getDonors() as $donor) {
            $donors[] = $donor->getShortname();
        }

        return implode(', ', $donors);
    }
}
