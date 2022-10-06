<?php

declare(strict_types=1);

namespace Export;

use DateTime;
use Entity\Organization;
use MapperDeprecated\LocationMapper;
use Enum\ProductCategoryType;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Translation\TranslatorInterface;
use Entity\User;
use Entity\Invoice;
use Entity\Vendor;
use Repository\SmartcardPurchaseRepository;

class SmartcardInvoiceExport
{
    public const TEMPLATE_VERSION = '1.3';
    public const DATE_FORMAT = 'j-n-y';
    public const EOL = "\r\n";

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
    public function __construct(
        TranslatorInterface $translator,
        LocationMapper $locationMapper,
        SmartcardPurchaseRepository $purchaseRepository
    ) {
        $this->translator = $translator;
        $this->locationMapper = $locationMapper;
        $this->purchaseRepository = $purchaseRepository;
    }

    public function export(Invoice $invoice, Organization $organization, User $user, string $language)
    {
        $countryIso3 = self::extractCountryIso3($invoice->getVendor());

        $this->translator->setLocale($language);

        $foodValue = $this->purchaseRepository->sumPurchasesRecordsByCategoryType($invoice, ProductCategoryType::FOOD);
        $nonFoodValue = $this->purchaseRepository->sumPurchasesRecordsByCategoryType($invoice, ProductCategoryType::NONFOOD);
        $cashValue = $this->purchaseRepository->sumPurchasesRecordsByCategoryType($invoice, ProductCategoryType::CASHBACK);
        $currency = $invoice->getCurrency();

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        self::formatCells($worksheet);

        $lastRow = self::buildHeader($worksheet, $this->translator, $organization, $invoice, $this->locationMapper);
        $lastRow = self::buildBody($worksheet, $this->translator, $invoice->getValue(), $foodValue, $nonFoodValue, $cashValue, $currency, $lastRow + 1);
        $lastRow = self::buildFooter($worksheet, $this->translator, $organization, $user, $lastRow + 3);
        $lastRow = self::buildAnnex($worksheet, $this->translator, $this->purchaseRepository, $invoice, $lastRow + 2);
        self::buildFooter($worksheet, $this->translator, $organization, $user, $lastRow + 3);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        $slugger = new AsciiSlugger();

        $id = sprintf('%05d', $invoice->getId());
        $vendorName = $slugger->slug($invoice->getVendor()->getName());
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

    private static function buildHeader(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, Invoice $invoice, LocationMapper $locationMapper): int
    {
        self::buildHeaderFirstLineBoxes($worksheet, $translator, $organization, $invoice);

        self::buildHeaderSecondLine($worksheet, $translator, $organization, $invoice, $locationMapper, 7);
        self::buildHeaderThirdLine($worksheet, $translator, $organization, $invoice, 9);
        self::buildHeaderFourthLine($worksheet, $translator, $organization, $invoice, 11);

        // self::setSmallBorder($worksheet, 'B7:J10');

        return 16;
    }

    /**
     * Line with Boxes with invoice No. and logos
     *
     * @param Worksheet $worksheet
     * @param TranslatorInterface $translator
     * @param Organization $organization
     * @param Invoice $invoice
     *
     * @throws Exception
     */
    private static function buildHeaderFirstLineBoxes(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, Invoice $invoice): void
    {
        $worksheet->getRowDimension(2)->setRowHeight(24.02);
        $worksheet->getRowDimension(3)->setRowHeight(19.70);
        $worksheet->getRowDimension(5)->setRowHeight(26.80);

        // Temporary Invoice No. box
        $countryIso3 = self::extractCountryIso3($invoice->getVendor());
        $humansisInvoiceNo = $invoice->getInvoiceNo();
        $vendor = sprintf('%03d', $invoice->getVendor()->getId());
        $date = $invoice->getInvoicedAt()->format('y');
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
        $worksheet->setCellValue("B5", "Invoice" . ' ' . $translator->trans("Invoice"));
        $worksheet->getStyle('B5')->getFont()
            ->setBold(true)
            ->setSize(22)
            ->setName('Arial');
        $worksheet->getStyle('B5')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private static function buildHeaderSecondLine(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, Invoice $invoice, LocationMapper $locationMapper, int $row1): void
    {
        $row2 = $row1 + 1;

        // structure
        $worksheet->mergeCells("C$row1:D$row2");
        $worksheet->mergeCells("E$row1:G$row2");
        $worksheet->mergeCells("I$row1:J$row2");
        // data
        self::undertranslatedSmallHeadline($worksheet, $translator, "Customer", "B", $row1);
        $worksheet->setCellValue("C$row1", self::addTrans($translator, $organization->getName(), self::EOL));

        if (null === $invoice->getProjectInvoiceAddressLocal() && null === $invoice->getProjectInvoiceAddressEnglish()) {
            $worksheet->setCellValue("E$row1", $translator->trans("{$organization->getName()} address missing"));
        } else {
            $worksheet->setCellValue("E$row1", $invoice->getProjectInvoiceAddressEnglish() . "\n" . $invoice->getProjectInvoiceAddressLocal());
            $worksheet->getStyle("E$row1")->getAlignment()->setWrapText(true);
        }

        $worksheet->setCellValue("I$row1", $invoice->getInvoicedAt()->format(self::DATE_FORMAT));
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

    private static function buildHeaderThirdLine(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, Invoice $invoice, int $row1): void
    {
        $row2 = $row1 + 1;

        // structure
        $worksheet->mergeCells("C$row1:G$row2");
        $worksheet->mergeCells("I$row1:J$row2");
        // data
        self::undertranslatedSmallHeadline($worksheet, $translator, "Supplier", "B", $row1);
        $worksheet->setCellValue("C$row1", $invoice->getVendor()->getName());
        self::undertranslatedSmallHeadline($worksheet, $translator, "Vendor No.", "H", $row1);
        $worksheet->setCellValue("I$row1", $invoice->getVendorNo());
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

    private static function buildHeaderFourthLine(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, Invoice $invoice, int $row1): void
    {
        $row2 = $row1 + 1;
        $row3 = $row1 + 2;

        // structure
        $worksheet->mergeCells("B$row1:B$row3");
        $worksheet->mergeCells("F$row1:G$row1");
        $worksheet->mergeCells("F$row2:G$row3");
        $worksheet->mergeCells("C$row1:C$row3");
        // data
        $worksheet->setCellValue("B$row1", self::addTrans($translator, 'Contract No.', self::EOL));
        $worksheet->setCellValue("C$row1", $invoice->getContractNo());
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Period Start', 'D', $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Period End', 'E', $row1);
        $worksheet->setCellValue("F$row1", self::addTrans($translator, 'Project'));
        $worksheet->setCellValue("F$row2", $invoice->getProject() ? $invoice->getProject()->getName() : '~');
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Cash', 'H', $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Cheque', 'I', $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Bank', 'J', $row1);
        $firstPurchaseDate = null;
        $lastPurchaseDate = null;
        foreach ($invoice->getPurchases() as $purchase) {
            if (null === $firstPurchaseDate || $firstPurchaseDate > $purchase->getCreatedAt()->getTimestamp()) {
                $firstPurchaseDate = $purchase->getCreatedAt()->getTimestamp();
            }
            if (null === $lastPurchaseDate || $lastPurchaseDate < $purchase->getCreatedAt()->getTimestamp()) {
                $lastPurchaseDate = $purchase->getCreatedAt()->getTimestamp();
            }
        }
        $worksheet->setCellValue("D$row3", date(self::DATE_FORMAT, $firstPurchaseDate));
        $worksheet->setCellValue("E$row3", date(self::DATE_FORMAT, $lastPurchaseDate));
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
        $worksheet->getStyle("F$row2")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private static function buildBodyHeader(Worksheet $worksheet, TranslatorInterface $translator, int $row): void
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

    private static function buildBodyLine(Worksheet $worksheet, TranslatorInterface $translator, string $mainText, string $value, string $currency, int $row1): void
    {
        $row2 = $row1 + 1;

        // structure
        $worksheet->mergeCells("B$row1:G$row2");
        $worksheet->mergeCells("H$row1:I$row2");
        $worksheet->mergeCells("J$row1:J$row2");
        // data
        $worksheet->setCellValue("B$row1", self::addTrans($translator, $mainText, self::EOL));
        $worksheet->setCellValue('H' . $row1, $value);
        $worksheet->setCellValue('J' . $row1, $currency);
        // style
        $worksheet->getRowDimension($row1)->setRowHeight(20);
        $worksheet->getRowDimension($row2)->setRowHeight(20);
        self::setImportantInfo($worksheet, "B$row1:J$row2");
        self::setSmallBorder($worksheet, "H$row1:J$row2");
    }

    private static function buildBody(
        Worksheet $worksheet,
        TranslatorInterface $translator,
        string $totalValue,
        string $foodValue,
        string $nonFoodValue,
        string $cashValue,
        string $currency,
        int $row1
    ): int {
        $row2 = $row1 + 1;
        $row3 = $row1 + 2;

        self::buildBodyHeader($worksheet, $translator, $row1);

        // ----------------------- Prices by CategoryType
        self::buildBodyLine(
            $worksheet,
            $translator,
            'SmartCards redemption payment - Food Items',
            sprintf('%.2f', $foodValue),
            $currency,
            $row2
        );
        self::buildBodyLine(
            $worksheet,
            $translator,
            'SmartCards redemption payment - Non-Food Items',
            sprintf('%.2f', $nonFoodValue),
            $currency,
            $row2 + 2
        );
        self::buildBodyLine(
            $worksheet,
            $translator,
            'SmartCards redemption payment - Cashback',
            sprintf('%.2f', $cashValue),
            $currency,
            $row2 + 4
        );
        self::setSmallBorder($worksheet, "B" . $row2 . ":J" . ($row2 + 5));

        $rowEnd = $row2 + 5;

        // ----------------------- info
        $row1 = $rowEnd + 1;
        $worksheet->mergeCells("B$row1:J$row1");
        self::sidetranslated($worksheet, $translator, 'Itemized breakdown in Annex I', "B", $row1);
        // style
        self::setMinorText($worksheet, "B$row1");
        $worksheet->getStyle("B$row1")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getRowDimension($row1)->setRowHeight(15);

        // ----------------------- Total
        $row1 = $row1 + 2;
        // structure
        $worksheet->mergeCells("B$row1:G$row1");
        $worksheet->mergeCells("H$row1:I$row1");
        // data
        self::sidetranslatedSmallHeadline($worksheet, $translator, 'Total Amount to be Paid', "B", $row1);
        $worksheet->setCellValue("H" . $row1, sprintf("%.2f", $totalValue));
        $worksheet->setCellValue("J" . $row1, $currency);
        // style
        $worksheet->getRowDimension($row1)->setRowHeight(30);
        self::setImportantInfo($worksheet, "B" . $row1);
        self::setImportantFilledInfo($worksheet, "H" . $row1);
        self::setImportantFilledInfo($worksheet, "J" . $row1);
        self::setSmallBorder($worksheet, "B" . $row1 . ":J" . $row1);
        $worksheet->getStyle("B" . $row1 . ":J" . $row1)->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_DOUBLE);

        return $row1 + 4;
    }

    private static function buildAnnex(Worksheet $worksheet, TranslatorInterface $translator, SmartcardPurchaseRepository $purchaseRepository, Invoice $invoice, int $lineStart): int
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
        $worksheet->mergeCells("D$row1:F$row1");
        $worksheet->mergeCells("D$row2:F$row2");
        $worksheet->mergeCells("G$row1:H$row1");
        $worksheet->mergeCells("G$row2:H$row2");
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Item', "B", $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Item type', "D", $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Total Amount per Item', "G", $row1);
        self::undertranslatedSmallHeadline($worksheet, $translator, 'Currency', "I", $row1);
        $worksheet->getRowDimension($row1)->setRowHeight(18);
        $worksheet->getRowDimension($row2)->setRowHeight(18);
        self::setSmallBorder($worksheet, "B$row1:I$row2");
        $worksheet->getStyle("B$row1:I$row2")->getAlignment()->setWrapText(true);
        self::setSoftBackground($worksheet, "B$row1:I$row2");

        // table with purchases
        $lineStart += 3;
        $purchasedProducts = $purchaseRepository->countPurchasesRecordsByInvoice($invoice);
        foreach ($purchasedProducts as $purchasedProduct) {
            ++$lineStart;
            $worksheet->mergeCells("B$lineStart:C$lineStart");
            $worksheet->mergeCells("D$lineStart:F$lineStart");
            $worksheet->mergeCells("G$lineStart:H$lineStart");
            self::sidetranslated($worksheet, $translator, $purchasedProduct['name'], "B", $lineStart);
            // temporary removed because PIN-1651: current data are incorrect, distributed by Qty 1 for everything
            // $worksheet->setCellValue('D'.$lineStart, $purchasedProduct['quantity']);
            // self::sidetranslated($worksheet, $translator, $purchasedProduct['unit'], "E", $lineStart);
            $worksheet->setCellValue('D' . $lineStart, self::addTrans($translator, $purchasedProduct['categoryType']));
            $worksheet->setCellValue('G' . $lineStart, sprintf('%.2f', $purchasedProduct['value']));
            $worksheet->setCellValue('I' . $lineStart, $purchasedProduct['currency']);

            self::setSmallBorder($worksheet, "B$lineStart:I$lineStart");
            $worksheet->getStyle('B' . $lineStart)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $worksheet->getStyle('D' . $lineStart)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // total
        $lineStart += 2;
        $worksheet->mergeCells('C' . $lineStart . ':F' . $lineStart);
        $worksheet->mergeCells('G' . $lineStart . ':H' . $lineStart);
        $currency = '';
        foreach ($invoice->getPurchases() as $purchase) {
            $currency = $purchase->getSmartcard()->getCurrency();
            break;
        }
        self::sidetranslatedSmallHeadline($worksheet, $translator, 'Total per Vendor and Period', "C", $lineStart);
        $worksheet->setCellValue('G' . $lineStart, sprintf('%.2f', $invoice->getValue()));
        $worksheet->setCellValue('I' . $lineStart, $currency);
        self::setSmallHeadline($worksheet, "C$lineStart:I$lineStart");
        self::setSmallBorder($worksheet, "C$lineStart:I$lineStart");

        return $lineStart + 1;
    }

    private static function buildFooter(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, User $user, $nextRow): int
    {
        // supplier signature description
        self::sidetranslated($worksheet, $translator, "Supplier's Signature", "B", $nextRow);
        $worksheet->mergeCells('B' . $nextRow . ':D' . $nextRow);
        $worksheet->mergeCells('E' . $nextRow . ':J' . $nextRow);
        $worksheet->getRowDimension($nextRow)->setRowHeight(40);
        $worksheet->getStyle('B' . $nextRow . ':D' . $nextRow)->getFont()
            ->setSize(12);
        $worksheet->getStyle('B' . $nextRow . ':D' . $nextRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle('E' . $nextRow . ':J' . $nextRow)->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_DASHED);

        // supplier signature underline
        ++$nextRow;
        self::sidetranslated($worksheet, $translator, "Signature/Name", "E", $nextRow);
        $worksheet->mergeCells('E' . $nextRow . ':J' . $nextRow);
        $worksheet->getStyle('E' . $nextRow . ':J' . $nextRow)->getFont()
            ->setItalic(true)
            ->setSize(9);

        // organization signature description
        $nextRow += 2;
        $worksheet->mergeCells('B' . $nextRow . ':D' . $nextRow);
        $worksheet->mergeCells('E' . $nextRow . ':J' . $nextRow);
        self::sidetranslated($worksheet, $translator, $organization->getName() . " Signature", "B", $nextRow);
        $worksheet->getRowDimension($nextRow)->setRowHeight(40);
        $worksheet->getStyle('B' . $nextRow . ':D' . $nextRow)->getFont()
            ->setSize(12);
        $worksheet->getStyle('B' . $nextRow . ':D' . $nextRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle('E' . $nextRow . ':J' . $nextRow)->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_DASHED);

        // organization signature underline
        ++$nextRow;
        self::sidetranslated($worksheet, $translator, "Signature/Name/Position", "E", $nextRow);
        $worksheet->mergeCells('E' . $nextRow . ':J' . $nextRow);
        $worksheet->getStyle('E' . $nextRow . ':J' . $nextRow)->getFont()
            ->setItalic(true)
            ->setSize(9);

        // Template version: [v]
        ++$nextRow;
        self::setMinorText($worksheet, 'H' . $nextRow . ':H' . ($nextRow + 2));
        $worksheet->setCellValue('H' . $nextRow, $translator->trans('Invoice template version', ['versionNumber' => self::TEMPLATE_VERSION]));
        // Generated by: [login or PIN staff name]
        ++$nextRow;
        self::setMinorText($worksheet, 'H' . $nextRow . ':H' . ($nextRow + 2));
        $worksheet->setCellValue('H' . $nextRow, $translator->trans('generated_by', ['username' => $user->getUsername()]));
        // Generated on: [date]
        ++$nextRow;
        $today = new DateTime();
        $worksheet->setCellValue('H' . $nextRow, $translator->trans('generated_on', ['date' => $today->format(self::DATE_FORMAT)]));
        // Unique document integrity ID: BLANK
        ++$nextRow;
        $worksheet->setCellValue('H' . $nextRow, $translator->trans('checksum', ['checksum' => '']));

        // delimiter of page end
        ++$nextRow;
        $worksheet->getStyle('B' . $nextRow . ':J' . $nextRow)->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_DOUBLE);

        return $nextRow;
    }

    private static function setSpecialBackground(Worksheet $worksheet, string $cellCoordination)
    {
        $worksheet->getStyle($cellCoordination)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('C5E0B4'));
    }

    private static function setSoftBackground(Worksheet $worksheet, string $cellCoordination)
    {
        $worksheet->getStyle($cellCoordination)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('C0C0C0'));
    }

    private static function setSmallHeadline(Worksheet $worksheet, string $cellCoordination)
    {
        $worksheet->getStyle($cellCoordination)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private static function setMinorText(Worksheet $worksheet, string $cellCoordination)
    {
        $worksheet->getStyle($cellCoordination)->getFont()
            ->setBold(false)
            ->setSize(10)
            ->setName('Arial');
        $worksheet->getStyle($cellCoordination)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }

    private static function setImportantFilledInfo(Worksheet $worksheet, string $cellCoordination)
    {
        self::setSpecialBackground($worksheet, $cellCoordination);
        $worksheet->getStyle($cellCoordination)->getFont()
            ->setBold(true)
            ->setSize(15)
            ->setName('Arial');
        $worksheet->getStyle($cellCoordination)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private static function setImportantInfo(Worksheet $worksheet, string $cellCoordination)
    {
        $worksheet->getStyle($cellCoordination)->getFont()
            ->setBold(true)
            ->setSize(15)
            ->setName('Arial');
        $worksheet->getStyle($cellCoordination)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private static function undertranslatedSmallHeadline(Worksheet $worksheet, TranslatorInterface $translator, string $importantInfo, string $column, int $row): void
    {
        $worksheet->setCellValue($column . $row, $importantInfo);
        $worksheet->setCellValue($column . ($row + 1), self::translate($translator, $importantInfo));
        self::setSmallHeadline($worksheet, $column . $row . ':' . $column . ($row + 1));
        $worksheet->getStyle($column . $row . ':' . $column . ($row + 1))->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyle($column . $row . ':' . $column . ($row + 1))->getBorders()
            ->getInside()
            ->setBorderStyle(Border::BORDER_NONE);
    }

    private static function sidetranslated(Worksheet $worksheet, TranslatorInterface $translator, string $importantInfo, string $column, int $row): void
    {
        $worksheet->setCellValue($column . $row, self::addTrans($translator, $importantInfo));
    }

    private static function sidetranslatedSmallHeadline(Worksheet $worksheet, TranslatorInterface $translator, string $importantInfo, string $column, int $row): void
    {
        $worksheet->setCellValue($column . $row, self::addTrans($translator, $importantInfo));
        self::setSmallHeadline($worksheet, $column . $row);
        $worksheet->getStyle($column . $row)->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private static function addTrans(TranslatorInterface $translator, string $text, string $delimiter = ' '): string
    {
        $translation = $translator->trans($text);
        if ($translation == $text) {
            return $text;
        }

        return $text . $delimiter . $translation;
    }

    private static function translate(TranslatorInterface $translator, string $text): string
    {
        $translation = $translator->trans($text);
        if ($translation == $text) {
            return '';
        }

        return $translation;
    }

    private static function setSmallBorder(Worksheet $worksheet, string $cellCoordination)
    {
        $worksheet->getStyle($cellCoordination)->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private static function extractCountryIso3(Vendor $vendor): string
    {
        if (!$vendor->getLocation()) {
            return 'ALL';
        }

        return $vendor->getLocation()->getCountryIso3();
    }
}
