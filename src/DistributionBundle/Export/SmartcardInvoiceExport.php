<?php

declare(strict_types=1);

namespace DistributionBundle\Export;

use CommonBundle\Entity\Organization;
use CommonBundle\Mapper\LocationMapper;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Translation\TranslatorInterface;
use UserBundle\Entity\User;
use VoucherBundle\Entity\SmartcardRedemptionBatch;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Repository\SmartcardPurchaseRepository;

class SmartcardInvoiceExport
{
    const TEMPLATE_VERSION = '1.2';
    const DATE_FORMAT = 'j-n-y';
    const EOL = "\r\n";

    /** @var TranslatorInterface */
    private $translator;

    /** @var LocationMapper */
    private $locationMapper;

    /** @var SmartcardPurchaseRepository */
    private $purchaseRepository;

    /**
     * SmartcardInvoiceExport constructor.
     *
     * @param TranslatorInterface $translator
     * @param LocationMapper $locationMapper
     * @param SmartcardPurchaseRepository $purchaseRepository
     */
    public function __construct(TranslatorInterface $translator, LocationMapper $locationMapper,
                                SmartcardPurchaseRepository $purchaseRepository
    )
    {
        $this->translator = $translator;
        $this->locationMapper = $locationMapper;
        $this->purchaseRepository = $purchaseRepository;
    }

    public function export(SmartcardRedemptionBatch $batch, Organization $organization, User $user, string $language)
    {
        $countryIso3 = self::extractCountryIso3($batch->getVendor());

        $this->translator->setLocale($language);

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        self::formatCells($worksheet);

        $lastRow = self::buildHeader($worksheet, $this->translator, $organization, $batch, $this->locationMapper);
        $lastRow = self::buildBody($worksheet, $this->translator, $batch, $lastRow + 1);
        $lastRow = self::buildFooter($worksheet, $this->translator, $organization, $user, $lastRow + 3);
        $lastRow = self::buildAnnex($worksheet, $this->translator, $this->purchaseRepository, $batch, $lastRow + 2);
        self::buildFooter($worksheet, $this->translator, $organization, $user, $lastRow + 3);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        $slugger = new AsciiSlugger();

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
        $worksheet->getRowDimension(2)->setRowHeight(24.02);
        $worksheet->getRowDimension(3)->setRowHeight(19.70);
        $worksheet->getRowDimension(5)->setRowHeight(26.80);

        // Temporary Invoice No. box
        $countryIso3 = self::extractCountryIso3($batch->getVendor());
        $humansisInvoiceNo = $batch->getInvoiceNo();
        $vendor = sprintf('%03d', $batch->getVendor()->getId());
        $date = $batch->getRedeemedAt()->format('y');
        $worksheet->setCellValue('B2', 'Temporary Invoice No.');
        $worksheet->setCellValue('B3', "{$countryIso3}EV{$date}{$humansisInvoiceNo}");
        self::setSmallHeadline($worksheet, 'B2:B3');
        self::setSmallBorder($worksheet, 'B2:B3');

        // Humansis Invoice No. box
        $worksheet->mergeCells('E2:F2');
        $worksheet->mergeCells('E3:F3');
        $worksheet->setCellValue('E2', 'Humansis Invoice No.');
        $worksheet->setCellValue('E3', $humansisInvoiceNo);
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
        $worksheet->mergeCells("I$row1:J$row2");
        // data
        self::undertranslatedSmallHeadline($worksheet, $translator, "Customer", "B", $row1);
        $worksheet->setCellValue("C$row1", self::addTrans($translator, $organization->getName(), self::EOL));

        if (null === $batch->getProjectInvoiceAddressLocal() && null === $batch->getProjectInvoiceAddressEnglish()) {
            $worksheet->setCellValue("E$row1", $translator->trans("{$organization->getName()} address missing", [], 'invoice'));
        } else {
            $worksheet->setCellValue("E$row1",$batch->getProjectInvoiceAddressEnglish() . "\n" . $batch->getProjectInvoiceAddressLocal());
            $worksheet->getStyle("E$row1")->getAlignment()->setWrapText(true);
        }

        $worksheet->setCellValue("I$row1", $batch->getRedeemedAt()->format(self::DATE_FORMAT));
        self::undertranslatedSmallHeadline($worksheet, $translator, "Invoice Date", "H", $row1);
        // style
        $worksheet->getRowDimension("$row1")->setRowHeight(25);
        $worksheet->getRowDimension("$row2")->setRowHeight(25);
        $worksheet->getStyle("C$row1")->getAlignment()->setWrapText(true);
        $worksheet->getStyle("E$row1")->getAlignment()->setWrapText(true);
        $worksheet->getStyle("B$row1")->getAlignment()->setWrapText(true);
        self::setImportantFilledInfo($worksheet, "C$row1:G$row2");
        self::setImportantFilledInfo($worksheet, "I$row1:J$row2");
        self::setSmallHeadline($worksheet, "E$row1:G$row2");
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
        $worksheet->setCellValue("I$row1", $batch->getVendorNo());
        // style
        $worksheet->getRowDimension($row1)->setRowHeight(20);
        $worksheet->getRowDimension($row2)->setRowHeight(20);
        $worksheet->getStyle("H$row1")->getAlignment()->setWrapText(true);
        self::setImportantFilledInfo($worksheet, "C$row1");
        self::setImportantFilledInfo($worksheet, "I$row1");
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
        $worksheet->mergeCells("B$row1:B$row3");
        $worksheet->mergeCells("F$row1:G$row3");
        $worksheet->mergeCells("C$row1:C$row3");
        // data
        $worksheet->setCellValue("B$row1", self::addTrans($translator, 'Contract No.', self::EOL));
        $worksheet->setCellValue("C$row1", $batch->getContractNo());
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Period Start', 'D', $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Period End', 'E', $row1);
        $worksheet->setCellValue("F$row1", self::addTrans($translator, 'Payment Method', self::EOL));
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Cash', 'H', $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Cheque', 'I', $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Bank', 'J', $row1);
        $firstPurchaseDate = null;
        $lastPurchaseDate = null;
        foreach ($batch->getPurchases() as $purchase) {
            if (null === $firstPurchaseDate || $firstPurchaseDate > $purchase->getCreatedAt()->getTimestamp()) {
                $firstPurchaseDate = $purchase->getCreatedAt()->getTimestamp();
            }
            if (null === $lastPurchaseDate || $lastPurchaseDate < $purchase->getCreatedAt()->getTimestamp()) {
                $lastPurchaseDate = $purchase->getCreatedAt()->getTimestamp();
            }
        }
        $worksheet->setCellValue("D$row3", date( self::DATE_FORMAT, $firstPurchaseDate));
        $worksheet->setCellValue("E$row3", date( self::DATE_FORMAT, $lastPurchaseDate));
        $worksheet->setCellValue("H$row3", "x");
        $worksheet->setCellValue("I$row3", "");
        $worksheet->setCellValue("J$row3", "");
        // style
        $worksheet->getRowDimension($row1)->setRowHeight(25);
        $worksheet->getRowDimension($row2)->setRowHeight(20);
        $worksheet->getRowDimension($row3)->setRowHeight(25);
        self::setSmallHeadline($worksheet, "B$row3:J$row3");
        self::setSmallHeadline($worksheet, "B$row1");
        self::setSmallHeadline($worksheet, "F$row1");
        self::setImportantFilledInfo($worksheet, "C$row1");
        self::setImportantFilledInfo($worksheet, "H$row3");
        self::setImportantFilledInfo($worksheet, "I$row3");
        self::setImportantFilledInfo($worksheet, "J$row3");
        self::setSmallBorder($worksheet, "B$row3:J$row3");
        self::setSmallBorder($worksheet, "B$row1");
        self::setSmallBorder($worksheet, "F$row1");
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

        // style
        $worksheet->getRowDimension($row)->setRowHeight(30);
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
            '',
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
        $worksheet->getRowDimension($row1)->setRowHeight(30);
        self::setImportantInfo($worksheet, "B".$row1);
        self::setImportantFilledInfo($worksheet, "H".$row1);
        self::setImportantFilledInfo($worksheet, "J".$row1);
        self::setSmallBorder($worksheet, "B".$row1.":J".$row1);
        $worksheet->getStyle("B".$row1.":J".$row1)->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_DOUBLE);

        return $row1+4;
    }


    private static function buildAnnex(Worksheet $worksheet, TranslatorInterface $translator, SmartcardPurchaseRepository $purchaseRepository, SmartcardRedemptionBatch $batch, int $lineStart): int
    {
        // header
        $worksheet->mergeCells("C$lineStart:E$lineStart");
        self::sidetranslatedSmallHeadline($worksheet, $translator, 'Annex I', "B", $lineStart);
        self::sidetranslatedSmallHeadline($worksheet, $translator, 'Itemized Breakdown', "C", $lineStart);

        // table header
        $row1 = $lineStart + 2;
        $row2 = $lineStart + 3;
        $worksheet->mergeCells("B$row1:C$row1");
        $worksheet->mergeCells("B$row2:C$row2");
        $worksheet->mergeCells("G$row1:H$row1");
        $worksheet->mergeCells("G$row2:H$row2");
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Item', "B", $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Quantity', "D", $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Unit', "E", $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Unit Price', "F", $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Total Amount per Item', "G", $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Currency', "I", $row1);
        $worksheet->getRowDimension($row1)->setRowHeight(18);
        $worksheet->getRowDimension($row2)->setRowHeight(18);
        self::setSmallBorder($worksheet, "B$row1:I$row2");
        $worksheet->getStyle("B$row1:I$row2")->getAlignment()->setWrapText(true);
        self::setSoftBackground($worksheet, "B$row1:I$row2");

        // table with purchases
        $lineStart += 3;
        $purchasedProducts = $purchaseRepository->countPurchasesRecordsByBatch($batch);
        foreach ($purchasedProducts as $purchasedProduct) {
            ++$lineStart;
            $worksheet->mergeCells("B$lineStart:C$lineStart");
            $worksheet->mergeCells("G$lineStart:H$lineStart");
            self::sidetranslated($worksheet, $translator, $purchasedProduct['name'], "B", $lineStart);
            // temporary removed because PIN-1651: current data are incorrect, distributed by Qty 1 for everything
            // $worksheet->setCellValue('D'.$lineStart, $purchasedProduct['quantity']);
            // self::sidetranslated($worksheet, $translator, $purchasedProduct['unit'], "E", $lineStart);
            $worksheet->setCellValue('F'.$lineStart, '');
            $worksheet->setCellValue('G'.$lineStart, sprintf('%.2f', $purchasedProduct['value']));
            $worksheet->setCellValue('I'.$lineStart, $purchasedProduct['currency']);
            self::setSmallBorder($worksheet, "B$lineStart:I$lineStart");
        }

        // total
        $lineStart += 2;
        $worksheet->mergeCells('C'.$lineStart.':F'.$lineStart);
        $worksheet->mergeCells('G'.$lineStart.':H'.$lineStart);
        $currency = '';
        foreach ($batch->getPurchases() as $purchase) {
            $currency = $purchase->getSmartcard()->getCurrency();
            break;
        }
        self::sidetranslatedSmallHeadline($worksheet, $translator, 'Total per Vendor and Period', "C", $lineStart);
        $worksheet->setCellValue('G'.$lineStart, sprintf('%.2f', $batch->getValue()));
        $worksheet->setCellValue('I'.$lineStart, $currency);
        self::setSmallHeadline($worksheet,"C$lineStart:I$lineStart");
        self::setSmallBorder($worksheet,"C$lineStart:I$lineStart");

        return $lineStart+1;
    }

    private static function buildFooter(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, User $user, $nextRow): int
    {
        // supplier signature description
        self::sidetranslated($worksheet, $translator, "Supplier's Signature", "B", $nextRow);
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
        self::sidetranslated($worksheet, $translator, "Signature/Name", "E", $nextRow);
        $worksheet->mergeCells('E'.$nextRow.':J'.$nextRow);
        $worksheet->getStyle('E'.$nextRow.':J'.$nextRow)->getFont()
            ->setItalic(true)
            ->setSize(9);

        // organization signature description
        $nextRow += 2;
        $worksheet->mergeCells('B'.$nextRow.':D'.$nextRow);
        $worksheet->mergeCells('E'.$nextRow.':J'.$nextRow);
        self::sidetranslated($worksheet, $translator, $organization->getName()." Signature", "B", $nextRow);
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
        self::sidetranslated($worksheet, $translator, "Signature/Name/Position", "E", $nextRow);
        $worksheet->mergeCells('E'.$nextRow.':J'.$nextRow);
        $worksheet->getStyle('E'.$nextRow.':J'.$nextRow)->getFont()
            ->setItalic(true)
            ->setSize(9);

        // Template version: [v]
        ++$nextRow;
        self::setMinorText($worksheet, 'H'.$nextRow.':H'.($nextRow+2));
        $worksheet->setCellValue('H'.$nextRow, $translator->trans('Invoice template version', ['versionNumber'=>self::TEMPLATE_VERSION], 'invoice'));
        // Generated by: [login or PIN staff name]
        ++$nextRow;
        self::setMinorText($worksheet, 'H'.$nextRow.':H'.($nextRow+2));
        $worksheet->setCellValue('H'.$nextRow, $translator->trans('generated_by', ['username'=>$user->getUsername()], 'invoice'));
        // Generated on: [date]
        ++$nextRow;
        $today = new \DateTime();
        $worksheet->setCellValue('H'.$nextRow, $translator->trans('generated_on', ['date'=>$today->format(self::DATE_FORMAT)], 'invoice'));
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
        $worksheet->setCellValue($column.($row+1), self::translate($translator, $importantInfo));
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
        $worksheet->setCellValue($column.$row2, self::translate($translator, $importantInfo));
        $worksheet->setCellValue($column.$row3, self::addTrans($translator, $description));

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

    private static function sidetranslated(Worksheet $worksheet, TranslatorInterface $translator, string $importantInfo, string $column, int $row): void
    {
        $worksheet->setCellValue($column.$row, self::addTrans($translator, $importantInfo));
    }

    private static function sidetranslatedSmallHeadline(Worksheet $worksheet, TranslatorInterface $translator, string $importantInfo, string $column, int $row): void
    {
        $worksheet->setCellValue($column.$row, self::addTrans($translator, $importantInfo));
        self::setSmallHeadline($worksheet, $column.$row);
        $worksheet->getStyle($column.$row)->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private static function addTrans(TranslatorInterface $translator, string $text, string $delimiter = ' '): string
    {
        $translation = $translator->trans($text, [], 'invoice');
        if ($translation == $text) {
            return $text;
        }
        return $text.$delimiter.$translation;
    }

    private static function translate(TranslatorInterface $translator, string $text): string
    {
        $translation = $translator->trans($text, [], 'invoice');
        if ($translation == $text) {
            return '';
        }
        return $translation;
    }

    private static function setSmallBorder(Worksheet $worksheet, string $cellCoordination) {
        $worksheet->getStyle($cellCoordination)->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private static function extractCountryIso3(Vendor $vendor): string
    {
        if (!$vendor->getLocation()) {
           return 'ALL';
        }
        $adm1 = null;
        if ($vendor->getLocation()->getAdm1()) {
            $adm1 = $vendor->getLocation()->getAdm1();
        }
        if ($vendor->getLocation()->getAdm2()) {
            $adm1 = $vendor->getLocation()->getAdm2()->getAdm1();
        }
        if ($vendor->getLocation()->getAdm3()) {
            $adm1 = $vendor->getLocation()->getAdm3()->getAdm2()->getAdm1();
        }
        if ($vendor->getLocation()->getAdm4()) {
            $adm1 = $vendor->getLocation()->getAdm4()->getAdm3()->getAdm2()->getAdm1();
        }
        if (!$adm1) {
            return 'ALL';
        }
        return $adm1->getCountryISO3();
    }
}
