<?php

declare(strict_types=1);

namespace DistributionBundle\Export;

use CommonBundle\Entity\Organization;
use CommonBundle\Mapper\LocationMapper;
use CommonBundle\Utils\StringUtils;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Translation\TranslatorInterface;
use UserBundle\Entity\User;
use VoucherBundle\Entity\SmartcardRedemptionBatch;

class SmartcardInvoiceExport
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var LocationMapper */
    private $locationMapper;

    /**
     * SmartcardInvoiceExport constructor.
     *
     * @param TranslatorInterface $translator
     * @param LocationMapper      $locationMapper
     */
    public function __construct(TranslatorInterface $translator, LocationMapper $locationMapper)
    {
        $this->translator = $translator;
        $this->locationMapper = $locationMapper;
    }

    public function export(SmartcardRedemptionBatch $batch, Organization $organization, User $user)
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        self::formatCells($worksheet);

        $lastRow = self::buildHeader($worksheet, $this->translator, $organization, $batch, $this->locationMapper);
        $lastRow = self::buildBody($worksheet, $this->translator, $batch, $lastRow + 1);
        $lastRow = self::buildFooter($worksheet, $this->translator, $organization, $user, $lastRow + 3);
        $lastRow = self::buildAnnex($worksheet, $this->translator, $batch, $lastRow + 2);
        self::buildFooter($worksheet, $this->translator, $organization, $user, $lastRow + 3);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        $slugger = new AsciiSlugger();

        $countryIso3 = $batch->getProject()->getIso3();
        $id = sprintf('%05d', $batch->getId());
        $vendorName = $slugger->slug($batch->getVendor()->getName());
        $invoiceName = "{$countryIso3}EFV{$id}{$vendorName}.xlsx";

        $writer->save($invoiceName);
        return $invoiceName;
    }

    private static function formatCells(Worksheet $worksheet)
    {
        $worksheet->getColumnDimension('A')->setWidth(02.429);
        $worksheet->getColumnDimension('B')->setWidth(19.575);
        $worksheet->getColumnDimension('C')->setWidth(16.567);
        $worksheet->getColumnDimension('D')->setWidth(10.142);
        $worksheet->getColumnDimension('E')->setWidth(11.425);
        $worksheet->getColumnDimension('F')->setWidth(12.567);
        $worksheet->getColumnDimension('G')->setWidth(10.283);
        $worksheet->getColumnDimension('H')->setWidth(13.142);
        $worksheet->getColumnDimension('I')->setWidth(12.425);
        $worksheet->getColumnDimension('J')->setWidth(10.996);
        $worksheet->getColumnDimension('K')->setWidth(02.429);

        $worksheet->getRowDimension('A1:A10000')->setRowHeight(4.064);

        $worksheet->getStyle('A1:K10000')->getFont()
            ->setBold(true)
            ->setSize(10)
            ->setName('Arial');
        $worksheet->getStyle('A1:K10000')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private static function buildHeader(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, SmartcardRedemptionBatch $batch, LocationMapper $locationMapper): int
    {
        self::buildHeaderFirstLineBoxes($worksheet, $translator, $organization, $batch);

        self::buildHeaderSecondLine($worksheet, $translator, $organization, $batch, $locationMapper, 7);
        self::buildHeaderThirdLine($worksheet, $translator, $organization, $batch, 9);
        self::buildHeaderFourthLine($worksheet, $translator, $organization, $batch, 11);

        // self::setSmallBorder($worksheet, 'B7:J10');

        return 16;
    }

    /**
     * Line with Boxes with invoice No. and logos
     *
     * @param Worksheet                $worksheet
     * @param TranslatorInterface      $translator
     * @param Organization             $organization
     * @param SmartcardRedemptionBatch $batch
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private static function buildHeaderFirstLineBoxes(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, SmartcardRedemptionBatch $batch): void
    {
        $worksheet->getRowDimension('2')->setRowHeight(24.02);
        $worksheet->getRowDimension('3')->setRowHeight(19.70);
        $worksheet->getRowDimension('5')->setRowHeight(26.80);

        // Temporary Invoice No. box
        $countryIso3 = $batch->getProject()->getIso3();
        $humansisId = sprintf('%05d', $batch->getId());
        $vendor = sprintf('%03d', $batch->getVendor()->getId());
        $date = $batch->getRedeemedAt()->format('Ymd');
        $worksheet->setCellValue('B2', 'Temporary Invoice No.');
        $worksheet->setCellValue('B3', "{$countryIso3}{$vendor}{$date}{$humansisId}");
        self::setSmallHeadline($worksheet, 'B2:B3');
        self::setSmallBorder($worksheet, 'B2:B3');

        // Humansis Invoice No. box
        $worksheet->mergeCells('E2:F2');
        $worksheet->mergeCells('E3:F3');
        $worksheet->setCellValue('E2', 'Humansis Invoice No.');
        $worksheet->setCellValue('E3', $humansisId);
        self::setSmallHeadline($worksheet, 'E2:F3');
        self::setSmallBorder($worksheet, 'E2:F3');

        // Invoice No. box
        $worksheet->mergeCells('I2:J2');
        $worksheet->mergeCells('I3:J3');
        $worksheet->setCellValue('I2', 'Invoice No.');
        self::setSmallHeadline($worksheet, 'I2:J2');
        self::setSmallBorder($worksheet, 'I2:J3');

        // wide header "Invoice"
        $worksheet->mergeCells('B5:J5');
        $worksheet->setCellValue("B5", "Invoice".' '.$translator->trans("Invoice", [], 'invoice'));
        $worksheet->getStyle('B5')->getFont()
            ->setBold(true)
            ->setSize(22)
            ->setName('Arial');
        $worksheet->getStyle('B5')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private static function buildHeaderSecondLine(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, SmartcardRedemptionBatch $batch, LocationMapper $locationMapper, int $row1): void
    {
        $row2 = $row1 + 1;

        // structure
        $worksheet->mergeCells("C$row1:D$row2");
        $worksheet->mergeCells("E$row1:G$row2");
        $worksheet->mergeCells("E$row2:G$row2");
        $worksheet->mergeCells("I$row1:J$row2");
        // data
        self::undertranslatedSmallHeadline($worksheet, $translator, "Customer", "B", $row1);
        $worksheet->setCellValue("C$row1", $organization->getName());
        $worksheet->setCellValue("E$row1", $locationMapper->toName($batch->getVendor()->getLocation()));
        $worksheet->setCellValue("I$row1", $batch->getRedeemedAt()->format("j-n-y"));
        self::undertranslatedSmallHeadline($worksheet, $translator, "Invoice Date", "H", $row1);
        // style
        $worksheet->getRowDimension("$row1")->setRowHeight(25);
        $worksheet->getRowDimension("$row2")->setRowHeight(25);
        $worksheet->getStyle("E$row1")->getAlignment()->setWrapText(true);
        self::setImportantFilledInfo($worksheet, "C$row1:G$row2");
        self::setImportantFilledInfo($worksheet, "I$row1:J$row2");
        self::setSmallBorder($worksheet, "C$row1:D$row2");
        self::setSmallBorder($worksheet, "E$row1:G$row2");
        self::setSmallBorder($worksheet, "I$row1:J$row2");
    }

    private static function buildHeaderThirdLine(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, SmartcardRedemptionBatch $batch, int $row1): void
    {
        $row2 = $row1 + 1;
        
        // structure
        $worksheet->mergeCells("C$row1:G$row2");
        $worksheet->mergeCells("I$row1:J$row2");
        // data
        self::undertranslatedSmallHeadline($worksheet, $translator, "Supplier", "B", $row1);
        $worksheet->setCellValue("C$row1", $batch->getVendor()->getName());
        self::undertranslatedSmallHeadline($worksheet, $translator, "Vendor No.", "H", $row1);
        // style
        $worksheet->getRowDimension($row1)->setRowHeight(20);
        $worksheet->getRowDimension($row2)->setRowHeight(20);
        $worksheet->getStyle("H$row1")->getAlignment()->setWrapText(true);
        self::setImportantFilledInfo($worksheet, "C$row1");
        $worksheet->getStyle("C$row1:G$row2")->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyle("I$row1:J$row2")->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private static function buildHeaderFourthLine(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, SmartcardRedemptionBatch $batch, int $row1): void
    {
        $row2 = $row1+1;
        $row3 = $row1+2;

        // structure
        $worksheet->mergeCells("B$row2:B$row3");
        $worksheet->mergeCells("F$row1:G$row1");
        $worksheet->mergeCells("F$row2:G$row3");
        $worksheet->mergeCells("C$row1:C$row1");
        $worksheet->mergeCells("C$row2:C$row3");
        // data
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Contract No.', 'B', $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Period Start', 'D', $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Period End', 'E', $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Payment Method', 'F', $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Cash', 'H', $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Cheque', 'I', $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Bank', 'J', $row1);
        $worksheet->setCellValue("H$row3", "x");
        $worksheet->setCellValue("I$row3", "");
        $worksheet->setCellValue("J$row3", "");
        // style
        $worksheet->getRowDimension($row1)->setRowHeight(25);
        $worksheet->getRowDimension($row2)->setRowHeight(20);
        $worksheet->getRowDimension($row3)->setRowHeight(25);
        self::setSmallHeadline($worksheet, "B$row3:J$row3");
        self::setImportantFilledInfo($worksheet, "H$row3");
        self::setImportantFilledInfo($worksheet, "I$row3");
        self::setImportantFilledInfo($worksheet, "J$row3");
        self::setSmallBorder($worksheet, "B$row3:J$row3");
    }

    private static function buildBodyHeader(Worksheet $worksheet, TranslatorInterface $translator, SmartcardRedemptionBatch $batch, int $row): void
    {
        // structure
        $worksheet->mergeCells("B$row:G$row");
        $worksheet->mergeCells("H$row:I$row");

        // data
        self::sidetranslatedSmallHeadline($worksheet, $translator, 'Description', 'B', $row);
        self::sidetranslatedSmallHeadline($worksheet, $translator, 'Amount', 'H', $row);
        self::sidetranslatedSmallHeadline($worksheet, $translator, 'Currency', 'J', $row);

        // $worksheet->setCellValue('E$row', $translator->trans('qty', [], 'invoice'));
        // $worksheet->setCellValue('F$row', $translator->trans('unit', [], 'invoice'));
        // $worksheet->setCellValue('G$row', $translator->trans('unit_price', [], 'invoice'));

        // style
        $worksheet->getRowDimension($row)->setRowHeight(30);
        // self::setSmallHeadline($worksheet,"B13:J13');
        // self::setSmallBorder($worksheet,'B13:J13');
    }

    private static function buildBodyLine(Worksheet $worksheet, TranslatorInterface $translator, string $mainText, string $descriptionText, string $value, string $currency, int $row1): void
    {
        $row2 = $row1 + 1;
        $row3 = $row1 + 2;

        // structure
        $worksheet->mergeCells("B$row1:G$row1");
        $worksheet->mergeCells("B$row2:G$row2");
        $worksheet->mergeCells("B$row3:G$row3");
        $worksheet->mergeCells("H$row1:I$row3");
        $worksheet->mergeCells("J$row1:J$row3");
        // data
        self::undertranslatedBodyLine($worksheet, $translator, $mainText, $descriptionText, "B", $row1);
        $worksheet->setCellValue('H'.$row1, $value);
        $worksheet->setCellValue('J'.$row1, $currency);
        // style
        $worksheet->getRowDimension($row1)->setRowHeight(20);
        $worksheet->getRowDimension($row2)->setRowHeight(20);
        $worksheet->getRowDimension($row3)->setRowHeight(20);
        self::setImportantInfo($worksheet, "H$row1:J$row3");
        self::setSmallBorder($worksheet, "H$row1:J$row3");
    }

    private static function buildBody(Worksheet $worksheet, TranslatorInterface $translator, SmartcardRedemptionBatch $batch, int $row1): int
    {
        $row2 = $row1 + 1;
        $row3 = $row1 + 2;

        self::buildBodyHeader($worksheet, $translator, $batch, $row1);

        $currency = '';
        foreach ($batch->getPurchases() as $purchase) {
            $currency = $purchase->getSmartcard()->getCurrency();
            break;
        }

        // ----------------------- Food items
        self::buildBodyLine(
            $worksheet,
            $translator,
            'SmartCards redemption payment - Food Items',
            'Itemized breakdown in Annex I',
            sprintf('%.2f', $batch->getValue()),
            $currency,
            $row2
        );

        // ----------------------- Cash
        $rowFrom = $row2 + 3;
        $rowTo = $rowFrom + 3;
        self::buildBodyLine(
            $worksheet,
            $translator,
            'SmartCards redemption payment - Cash',
            '',
            '',
            $currency,
            $rowFrom
        );
        $worksheet->getStyle("B$rowFrom:G$rowTo")->getFont()->getColor()->setRGB('C0C0C0');

        // ----------------------- Total
        $row1 = $rowTo + 1;
        // structure
        $worksheet->mergeCells("B$row1:G$row1");
        $worksheet->mergeCells("H$row1:I$row1");
        // data
        self::sidetranslatedSmallHeadline($worksheet, $translator, 'Total Amount to be Paid', "B", $row1);
        $worksheet->setCellValue("H".$row1, sprintf("%.2f", $batch->getValue()));
        $worksheet->setCellValue("J".$row1, $currency);
        // style
        $worksheet->getRowDimension($row1)->setRowHeight(22.52);
        self::setImportantInfo($worksheet, "B".$row1);
        self::setImportantFilledInfo($worksheet, "H".$row1);
        self::setImportantFilledInfo($worksheet, "J".$row1);
        self::setSmallBorder($worksheet, "B".$row1.":J".$row1);
        $worksheet->getStyle("B".$row1.":J".$row1)->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_DOUBLE);

        return $row1+4;
    }


    private static function buildAnnex(Worksheet $worksheet, TranslatorInterface $translator, SmartcardRedemptionBatch $batch, int $lineStart): int
    {
        // header
        $worksheet->setCellValue('B'.$lineStart, $translator->trans('annex', [], 'invoice'));
        $worksheet->setCellValue('C'.$lineStart, $translator->trans('annex_description', [], 'invoice'));

        // table header
        $lineStart += 2;
        $worksheet->setCellValue('B'.$lineStart, $translator->trans('purchase_customer_id', [], 'invoice'));
        $worksheet->setCellValue('C'.$lineStart, $translator->trans('purchase_customer_first_name', [], 'invoice'));
        $worksheet->setCellValue('D'.$lineStart, $translator->trans('purchase_customer_family_name', [], 'invoice'));
        $worksheet->setCellValue('E'.$lineStart, $translator->trans('purchase_date', [], 'invoice'));
        $worksheet->setCellValue('F'.$lineStart, $translator->trans('purchase_time', [], 'invoice'));
        $worksheet->setCellValue('G'.$lineStart, $translator->trans('purchase_item', [], 'invoice'));
        $worksheet->setCellValue('H'.$lineStart, $translator->trans('purchase_unit', [], 'invoice'));
        $worksheet->setCellValue('I'.$lineStart, $translator->trans('purchase_item_total', [], 'invoice'));
        $worksheet->setCellValue('J'.$lineStart, $translator->trans('currency', [], 'invoice'));
        $worksheet->getRowDimension($lineStart)->setRowHeight(50);
        self::setSmallHeadline($worksheet, 'B'.$lineStart.':J'.$lineStart);
        self::setSmallBorder($worksheet, 'B'.$lineStart.':J'.$lineStart);
        self::setSoftBackground($worksheet, 'B'.$lineStart.':J'.$lineStart);
        $worksheet->getStyle('B'.$lineStart.':J'.$lineStart)->getAlignment()->setWrapText(true);

        // table with purchases
        foreach ($batch->getPurchases() as $purchase) {
            foreach ($purchase->getRecords() as $record) {
                ++$lineStart;
                $worksheet->setCellValue('B'.$lineStart, $purchase->getSmartcard()->getBeneficiary()->getId());
                $worksheet->setCellValue('C'.$lineStart, $purchase->getSmartcard()->getBeneficiary()->getPerson()->getLocalGivenName());
                $worksheet->setCellValue('D'.$lineStart, $purchase->getSmartcard()->getBeneficiary()->getPerson()->getLocalFamilyName());
                $worksheet->setCellValue('E'.$lineStart, $purchase->getCreatedAt()->format('Y-m-d'));
                $worksheet->setCellValue('F'.$lineStart, $purchase->getCreatedAt()->format('H:i'));
                $worksheet->setCellValue('G'.$lineStart, $record->getProduct()->getName());
                $worksheet->setCellValue('H'.$lineStart, $record->getProduct()->getUnit());
                $worksheet->setCellValue('I'.$lineStart, sprintf('%.2f', $record->getValue()));
                $worksheet->setCellValue('J'.$lineStart, $purchase->getSmartcard()->getCurrency());

                self::setSmallBorder($worksheet, 'B'.$lineStart.':J'.$lineStart);
            }
        }

        // total
        ++$lineStart;
        $worksheet->mergeCells('F'.$lineStart.':H'.$lineStart);
        $currency = '';
        foreach ($batch->getPurchases() as $purchase) {
            $currency = $purchase->getSmartcard()->getCurrency();
            break;
        }
        $worksheet->setCellValue('F'.$lineStart, $translator->trans('purchase_total', [], 'invoice'));
        $worksheet->setCellValue('I'.$lineStart, sprintf('%.2f', $batch->getValue()));
        $worksheet->setCellValue('J'.$lineStart, $currency);
        self::setSmallHeadline($worksheet,'F'.$lineStart);

        return $lineStart+1;
    }

    private static function buildFooter(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, User $user, $nextRow): int
    {
        // supplier signature description
        $worksheet->setCellValue('B'.$nextRow, $translator->trans('signature_recipient', [], 'invoice'));
        $worksheet->mergeCells('B'.$nextRow.':D'.$nextRow);
        $worksheet->mergeCells('E'.$nextRow.':J'.$nextRow);
        $worksheet->getRowDimension($nextRow)->setRowHeight(40);
        $worksheet->getStyle('B'.$nextRow.':D'.$nextRow)->getFont()
            ->setSize(12);
        $worksheet->getStyle('B'.$nextRow.':D'.$nextRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle('E'.$nextRow.':J'.$nextRow)->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_DASHED);

        // supplier signature underline
        ++$nextRow;
        $worksheet->setCellValue('E'.$nextRow, $translator->trans('signature_underline_individual', [], 'invoice'));
        $worksheet->mergeCells('E'.$nextRow.':J'.$nextRow);
        $worksheet->getStyle('E'.$nextRow.':J'.$nextRow)->getFont()
            ->setItalic(true)
            ->setSize(9);

        // organization signature description
        $nextRow += 2;
        $worksheet->mergeCells('B'.$nextRow.':D'.$nextRow);
        $worksheet->mergeCells('E'.$nextRow.':J'.$nextRow);
        $worksheet->setCellValue('B'.$nextRow, $translator->trans('signature_organization', ['organization'=>$organization->getName()], 'invoice'));
        $worksheet->getRowDimension($nextRow)->setRowHeight(40);
        $worksheet->getStyle('B'.$nextRow.':D'.$nextRow)->getFont()
            ->setSize(12);
        $worksheet->getStyle('B'.$nextRow.':D'.$nextRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle('E'.$nextRow.':J'.$nextRow)->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_DASHED);

        // organization signature underline
        ++$nextRow;
        $worksheet->setCellValue('E'.$nextRow, $translator->trans('signature_underline_organization', [], 'invoice'));
        $worksheet->mergeCells('E'.$nextRow.':J'.$nextRow);
        $worksheet->getStyle('E'.$nextRow.':J'.$nextRow)->getFont()
            ->setItalic(true)
            ->setSize(9);

        // Generated by: [login or PIN staff name]
        ++$nextRow;
        self::setMinorText($worksheet, 'H'.$nextRow.':H'.($nextRow+2));
        $worksheet->setCellValue('H'.$nextRow, $translator->trans('generated_by', ['username'=>$user->getUsername()], 'invoice'));
        // Generated on: [date]
        ++$nextRow;
        $worksheet->setCellValue('H'.$nextRow, $translator->trans('generated_on', ['date'=>time()], 'invoice'));
        // Unique document integrity ID: BLANK
        ++$nextRow;
        $worksheet->setCellValue('H'.$nextRow, $translator->trans('checksum', ['checksum'=>''], 'invoice'));

        // delimiter of page end
        ++$nextRow;
        $worksheet->getStyle('B'.$nextRow.':J'.$nextRow)->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_DOUBLE);

        return $nextRow;
    }

    private static function setSpecialBackground(Worksheet $worksheet, string $cellCoordination) {
        $worksheet->getStyle($cellCoordination)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('C5E0B4'));
    }

    private static function setSoftBackground(Worksheet $worksheet, string $cellCoordination) {
        $worksheet->getStyle($cellCoordination)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('C0C0C0'));
    }

    private static function setSmallHeadline(Worksheet $worksheet, string $cellCoordination) {
        $worksheet->getStyle($cellCoordination)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private static function setMinorText(Worksheet $worksheet, string $cellCoordination) {
        $worksheet->getStyle($cellCoordination)->getFont()
            ->setBold(false)
            ->setSize(10)
            ->setName('Arial');
        $worksheet->getStyle($cellCoordination)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }

    private static function setImportantFilledInfo(Worksheet $worksheet, string $cellCoordination) {
        self::setSpecialBackground($worksheet, $cellCoordination);
        $worksheet->getStyle($cellCoordination)->getFont()
            ->setBold(true)
            ->setSize(15)
            ->setName('Arial');
        $worksheet->getStyle($cellCoordination)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private static function setImportantInfo(Worksheet $worksheet, string $cellCoordination) {
        $worksheet->getStyle($cellCoordination)->getFont()
            ->setBold(true)
            ->setSize(15)
            ->setName('Arial');
        $worksheet->getStyle($cellCoordination)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private static function undertranslatedSmallHeadline(Worksheet $worksheet, TranslatorInterface $translator, string $importantInfo, string $column, int $row): void
    {
        $worksheet->setCellValue($column.$row, $importantInfo);
        $worksheet->setCellValue($column.($row+1), $translator->trans($importantInfo, [], 'invoice'));
        self::setSmallHeadline($worksheet, $column.$row.':'.$column.($row+1));
        $worksheet->getStyle($column.$row.':'.$column.($row+1))->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyle($column.$row.':'.$column.($row+1))->getBorders()
            ->getInside()
            ->setBorderStyle(Border::BORDER_NONE);
    }

    private static function undertranslatedBodyLine(Worksheet $worksheet, TranslatorInterface $translator, string $importantInfo, string $description, string $column, int $row1, ?string $color = null): void
    {
        $row2 = $row1 + 1;
        $row3 = $row1 + 2;

        $worksheet->setCellValue($column.$row1, $importantInfo);
        $worksheet->setCellValue($column.$row2, $translator->trans($importantInfo, [], 'invoice'));
        $worksheet->setCellValue($column.$row3, $description.' '.$translator->trans($description, [], 'invoice'));

        $worksheet->getStyle("$column$row1:$column$row2")->getFont()
            ->setBold(true)
            ->setSize(15)
            ->setName('Arial');
        $worksheet->getStyle("$column$row3:$column$row3")->getFont()
            ->setBold(false)
            ->setSize(10)
            ->setName('Arial');
        $worksheet->getStyle("$column$row1:$column$row3")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle("$column$row1:$column$row3")->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyle("$column$row1:$column$row3")->getBorders()
            ->getInside()
            ->setBorderStyle(Border::BORDER_NONE);
    }

    private static function sidetranslatedSmallHeadline(Worksheet $worksheet, TranslatorInterface $translator, string $importantInfo, string $column, int $row): void
    {
        $worksheet->setCellValue($column.$row, $importantInfo.' '.$translator->trans($importantInfo, [], 'invoice'));
        self::setSmallHeadline($worksheet, $column.$row);
        $worksheet->getStyle($column.$row)->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private static function setSmallBorder(Worksheet $worksheet, string $cellCoordination) {
        $worksheet->getStyle($cellCoordination)->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }
}
