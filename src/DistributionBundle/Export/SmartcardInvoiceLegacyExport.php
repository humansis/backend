<?php

declare(strict_types=1);

namespace DistributionBundle\Export;

use CommonBundle\Entity\Organization;
use CommonBundle\Mapper\LocationMapper;
use NewApiBundle\Enum\Domain;
use NewApiBundle\Utils\FileSystem\Exception\ImageException;
use NewApiBundle\Utils\FileSystem\Image;
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
use VoucherBundle\Entity\Invoice;
use VoucherBundle\Entity\Vendor;

class SmartcardInvoiceLegacyExport
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

    public function export(Invoice $invoice, Organization $organization, User $user)
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        self::formatCells($worksheet);

        $lastRow = self::buildHeader($worksheet, $this->translator, $organization, $invoice, $this->locationMapper);
        $lastRow = self::buildBody($worksheet, $this->translator, $invoice, $lastRow + 1);
        $lastRow = self::buildFooter($worksheet, $this->translator, $organization, $user, $lastRow + 3);
        $lastRow = self::buildAnnex($worksheet, $this->translator, $invoice, $lastRow + 2);
        self::buildFooter($worksheet, $this->translator, $organization, $user, $lastRow + 3);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        $slugger = new AsciiSlugger();

        $countryIso3 = self::extractCountryIso3($invoice->getVendor());
        $id = sprintf('%05d', $invoice->getId());
        $vendorName = $slugger->slug($invoice->getVendor()->getName());
        $invoiceName = "{$countryIso3}LEGACY{$id}{$vendorName}.xlsx";

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

        self::buildHeaderSecondLine($worksheet, $translator, $organization, $invoice, $locationMapper);
        self::buildHeaderThirdLine($worksheet, $translator, $organization, $invoice);
        self::buildHeaderFourthLine($worksheet, $translator);

        self::setSmallBorder($worksheet, 'B7:J10');

        self::buildBodyHeader($worksheet, $translator);

        return 13;
    }

    /**
     * Line with Boxes with invoice No. and logos
     *
     * @param Worksheet           $worksheet
     * @param TranslatorInterface $translator
     * @param Organization        $organization
     * @param Invoice             $invoice
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private static function buildHeaderFirstLineBoxes(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, Invoice $invoice): void
    {
        $worksheet->getRowDimension('2')->setRowHeight(24.02);
        $worksheet->getRowDimension('3')->setRowHeight(19.70);
        $worksheet->getRowDimension('5')->setRowHeight(26.80);

        // Temporary Invoice No. box
        $worksheet->setCellValue('B2', $translator->trans('temporary_invoice_no', [], Domain::INVOICE));
        self::setSmallHeadline($worksheet, 'B2:B3');
        self::setSmallBorder($worksheet, 'B2:B3');

        // vendor username box
        $worksheet->mergeCells('D2:E2');
        $worksheet->mergeCells('D3:E3');
        $worksheet->setCellValue('D2', 'Humansis Vendor Username');
        $worksheet->setCellValue('D3', $invoice->getVendor()->getUser()->getUsername());
        self::setSmallHeadline($worksheet, 'D2:E3');
        self::setSmallBorder($worksheet, 'D2:E3');

        // Invoice No. box
        $worksheet->mergeCells('F2:H2');
        $worksheet->mergeCells('F3:H3');
        $worksheet->setCellValue('F2', $translator->trans('invoice_no', [], Domain::INVOICE));
        self::setSmallHeadline($worksheet, 'F2:H3');
        self::setSmallBorder($worksheet, 'F2:H3');

        // wide header "Invoice"
        $worksheet->mergeCells('B5:J5');
        $worksheet->setCellValue('B5', $translator->trans('invoice', [], Domain::INVOICE));
        $worksheet->getStyle('B5')->getFont()
            ->setBold(true)
            ->setSize(22)
            ->setName('Arial');
        $worksheet->getStyle('B5')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // logo
        if ($organization->getLogo()) {
            try {
                $resource = Image::getImageResource($organization->getLogo());
                $drawing = new MemoryDrawing();
                $drawing->setCoordinates('J2');
                $drawing->setImageResource($resource);
                $drawing->setRenderingFunction(MemoryDrawing::RENDERING_DEFAULT);
                $drawing->setMimeType(MemoryDrawing::MIMETYPE_DEFAULT);
                $drawing->setHeight(60);
                $drawing->setWorksheet($worksheet);
            } catch (ImageException $e) {
                // invoice will be without logo
            }
        }
    }

    private static function buildHeaderSecondLine(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, Invoice $invoice, LocationMapper $locationMapper): void
    {
        // structure
        $worksheet->mergeCells('C7:D7');
        $worksheet->mergeCells('E7:G7');
        $worksheet->mergeCells('I7:J7');
        // data
        $worksheet->setCellValue('B7', $translator->trans('customer', [], Domain::INVOICE));
        $worksheet->setCellValue('C7', $organization->getName());
        $worksheet->setCellValue('E7', $locationMapper->toName($invoice->getVendor()->getLocation()));
        $worksheet->setCellValue('I7', $invoice->getInvoicedAt()->format('j-n-y'));
        $worksheet->setCellValue('H7', $translator->trans('invoice_date', [], Domain::INVOICE));
        // style
        $worksheet->getRowDimension('7')->setRowHeight(50);
        $worksheet->getStyle('E7')->getAlignment()->setWrapText(true);
        self::setSmallHeadline($worksheet, 'B7');
        self::setImportantFilledInfo($worksheet, 'C7:D7');
        self::setImportantFilledInfo($worksheet, 'E7:G7');
        self::setSmallHeadline($worksheet, 'H7');
        self::setImportantFilledInfo($worksheet, 'I7:J7');
    }

    private static function buildHeaderThirdLine(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, Invoice $invoice): void
    {
        // structure
        $worksheet->mergeCells('C8:G8');
        $worksheet->mergeCells('I8:J8');
        // data
        $worksheet->setCellValue('B8', $translator->trans('supplier_name', [], Domain::INVOICE));
        $worksheet->setCellValue('C8', $invoice->getVendor()->getName());
        $worksheet->setCellValue('H8', $translator->trans('supplier_no', [], Domain::INVOICE));
        // style
        $worksheet->getRowDimension('8')->setRowHeight(25);
        $worksheet->getStyle('H8')->getAlignment()->setWrapText(true);
        self::setSmallHeadline($worksheet, 'B8');
        self::setImportantFilledInfo($worksheet, 'C8');
        self::setSmallHeadline($worksheet, 'H8');
    }

    private static function buildHeaderFourthLine(Worksheet $worksheet, TranslatorInterface $translator): void
    {
        // structure
        $worksheet->mergeCells('B9:B10');
        $worksheet->mergeCells('C9:C10');
        $worksheet->mergeCells('F9:G10');
        // data
        $worksheet->setCellValue('B9', $translator->trans('contract_no', [], Domain::INVOICE));
        $worksheet->setCellValue('D9', $translator->trans('period_start', [], Domain::INVOICE));
        $worksheet->setCellValue('E9', $translator->trans('period_end', [], Domain::INVOICE));
        $worksheet->setCellValue('F9', $translator->trans('payment_method', [], Domain::INVOICE));
        $worksheet->setCellValue('H9', $translator->trans('cash', [], Domain::INVOICE));
        $worksheet->setCellValue('I9', $translator->trans('cheque', [], Domain::INVOICE));
        $worksheet->setCellValue('J9', $translator->trans('bank', [], Domain::INVOICE));
        $worksheet->setCellValue('H10', 'x');
        $worksheet->setCellValue('I10', '');
        $worksheet->setCellValue('J10', '');
        // style
        $worksheet->getRowDimension('9')->setRowHeight(25);
        $worksheet->getRowDimension('10')->setRowHeight(25);
        self::setSmallHeadline($worksheet, 'B9');
        self::setSmallHeadline($worksheet, 'D9');
        self::setSmallHeadline($worksheet, 'E9');
        self::setSmallHeadline($worksheet, 'F9');
        self::setSmallHeadline($worksheet, 'H9');
        self::setSmallHeadline($worksheet, 'I9');
        self::setSmallHeadline($worksheet, 'J9');
        self::setImportantFilledInfo($worksheet, 'H10');
        self::setImportantFilledInfo($worksheet, 'I10');
        self::setImportantFilledInfo($worksheet, 'J10');
    }

    private static function buildBodyHeader(Worksheet $worksheet, TranslatorInterface $translator): void
    {
        // structure
        $worksheet->mergeCells('B13:G13');
        $worksheet->mergeCells('H13:I13');

        // data
        $worksheet->setCellValue('B13', $translator->trans('description', [], Domain::INVOICE));
        $worksheet->setCellValue('H13', $translator->trans('amount', [], Domain::INVOICE));
        $worksheet->setCellValue('J13', $translator->trans('currency', [], Domain::INVOICE));

        $worksheet->setCellValue('E13', $translator->trans('qty', [], Domain::INVOICE));
        $worksheet->setCellValue('F13', $translator->trans('unit', [], Domain::INVOICE));
        $worksheet->setCellValue('G13', $translator->trans('unit_price', [], Domain::INVOICE));

        // style
        $worksheet->getRowDimension('13')->setRowHeight(30);
        self::setSmallHeadline($worksheet,'B13:J13');
        self::setSmallBorder($worksheet,'B13:J13');
    }

    private static function buildBody(Worksheet $worksheet, TranslatorInterface $translator, Invoice $invoice, int $lineStart): int
    {
        // ----------------------- Food items
        // structure
        $worksheet->mergeCells('B'.$lineStart.':G'.$lineStart);
        $worksheet->mergeCells('H'.$lineStart.':I'.$lineStart);
        // data
        $currency = '';
        foreach ($invoice->getPurchases() as $purchase) {
            $currency = $purchase->getSmartcard()->getCurrency();
            break;
        }
        $worksheet->setCellValue('B'.$lineStart, self::makeCommentedImportantInfo(
            $translator->trans('redemption_payment_items', [], Domain::INVOICE),
            $translator->trans('redemption_payment_items_description', [], Domain::INVOICE)
        ));
        $worksheet->setCellValue('H'.$lineStart, sprintf('%.2f', $invoice->getValue()));
        $worksheet->setCellValue('J'.$lineStart, $currency);
        // style
        $worksheet->getRowDimension($lineStart)->setRowHeight(50);
        self::setImportantInfo($worksheet, 'B'.$lineStart.':J'.$lineStart);
        self::setSmallBorder($worksheet, 'B'.$lineStart.':J'.$lineStart);

        // ----------------------- Cash
        $lineStart++;
        // structure
        $worksheet->mergeCells('B'.$lineStart.':G'.$lineStart);
        $worksheet->mergeCells('H'.$lineStart.':I'.$lineStart);

        // data
        $worksheet->setCellValue('B'.$lineStart, self::makeCommentedImportantInfo(
            $translator->trans('redemption_payment_cash', [], Domain::INVOICE),
            $translator->trans('redemption_payment_cash_description', [], Domain::INVOICE),
            'C0C0C0'
        ));
        $worksheet->setCellValue('H'.$lineStart, '');
        $worksheet->setCellValue('J'.$lineStart, $currency);

        // style
        $worksheet->getRowDimension($lineStart)->setRowHeight(40);
        self::setImportantInfo($worksheet, 'B'.$lineStart.':J'.$lineStart);
        self::setSmallBorder($worksheet, 'B'.$lineStart.':J'.$lineStart);

        // ----------------------- Total
        $lineStart += 2;
        // structure
        $worksheet->mergeCells('B'.$lineStart.':G'.$lineStart);
        $worksheet->mergeCells('H'.$lineStart.':I'.$lineStart);
        // data
        $worksheet->setCellValue('B'.$lineStart, $translator->trans('total_to_pay', [], Domain::INVOICE));
        $worksheet->setCellValue('H'.$lineStart, sprintf('%.2f', $invoice->getValue()));
        $worksheet->setCellValue('J'.$lineStart, $currency);
        // style
        $worksheet->getRowDimension($lineStart)->setRowHeight(22.52);
        self::setImportantInfo($worksheet, 'B'.$lineStart);
        self::setImportantFilledInfo($worksheet, 'H'.$lineStart);
        self::setImportantFilledInfo($worksheet, 'J'.$lineStart);
        self::setSmallBorder($worksheet, 'B'.$lineStart.':J'.$lineStart);
        $worksheet->getStyle('B'.$lineStart.':J'.$lineStart)->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_DOUBLE);

        return $lineStart+1;
    }


    private static function buildAnnex(Worksheet $worksheet, TranslatorInterface $translator, Invoice $invoice, int $lineStart): int
    {
        // header
        $worksheet->setCellValue('B'.$lineStart, $translator->trans('annex', [], Domain::INVOICE));
        $worksheet->setCellValue('C'.$lineStart, $translator->trans('annex_description', [], Domain::INVOICE));

        // table header
        $lineStart += 2;
        $worksheet->setCellValue('B'.$lineStart, $translator->trans('purchase_customer_id', [], Domain::INVOICE));
        $worksheet->setCellValue('C'.$lineStart, $translator->trans('purchase_customer_first_name', [], Domain::INVOICE));
        $worksheet->setCellValue('D'.$lineStart, $translator->trans('purchase_customer_family_name', [], Domain::INVOICE));
        $worksheet->setCellValue('E'.$lineStart, $translator->trans('purchase_date', [], Domain::INVOICE));
        $worksheet->setCellValue('F'.$lineStart, $translator->trans('purchase_time', [], Domain::INVOICE));
        $worksheet->setCellValue('G'.$lineStart, $translator->trans('purchase_item', [], Domain::INVOICE));
        $worksheet->setCellValue('H'.$lineStart, $translator->trans('purchase_unit', [], Domain::INVOICE));
        $worksheet->setCellValue('I'.$lineStart, $translator->trans('purchase_item_total', [], Domain::INVOICE));
        $worksheet->setCellValue('J'.$lineStart, $translator->trans('currency', [], Domain::INVOICE));
        $worksheet->getRowDimension($lineStart)->setRowHeight(50);
        self::setSmallHeadline($worksheet, 'B'.$lineStart.':J'.$lineStart);
        self::setSmallBorder($worksheet, 'B'.$lineStart.':J'.$lineStart);
        self::setSoftBackground($worksheet, 'B'.$lineStart.':J'.$lineStart);
        $worksheet->getStyle('B'.$lineStart.':J'.$lineStart)->getAlignment()->setWrapText(true);

        // table with purchases
        foreach ($invoice->getPurchases() as $purchase) {
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
        foreach ($invoice->getPurchases() as $purchase) {
            $currency = $purchase->getSmartcard()->getCurrency();
            break;
        }
        $worksheet->setCellValue('F'.$lineStart, $translator->trans('purchase_total', [], Domain::INVOICE));
        $worksheet->setCellValue('I'.$lineStart, sprintf('%.2f', $invoice->getValue()));
        $worksheet->setCellValue('J'.$lineStart, $currency);
        self::setSmallHeadline($worksheet,'F'.$lineStart);

        return $lineStart+1;
    }

    private static function buildFooter(Worksheet $worksheet, TranslatorInterface $translator, Organization $organization, User $user, $nextRow): int
    {
        // supplier signature description
        $worksheet->setCellValue('B'.$nextRow, $translator->trans('signature_recipient', [], Domain::INVOICE));
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
        $worksheet->setCellValue('E'.$nextRow, $translator->trans('signature_underline_individual', [], Domain::INVOICE));
        $worksheet->mergeCells('E'.$nextRow.':J'.$nextRow);
        $worksheet->getStyle('E'.$nextRow.':J'.$nextRow)->getFont()
            ->setItalic(true)
            ->setSize(9);

        // organization signature description
        $nextRow += 2;
        $worksheet->mergeCells('B'.$nextRow.':D'.$nextRow);
        $worksheet->mergeCells('E'.$nextRow.':J'.$nextRow);
        $worksheet->setCellValue('B'.$nextRow, $translator->trans('signature_organization', ['organization'=>$organization->getName()], Domain::INVOICE));
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
        $worksheet->setCellValue('E'.$nextRow, $translator->trans('signature_underline_organization', [], Domain::INVOICE));
        $worksheet->mergeCells('E'.$nextRow.':J'.$nextRow);
        $worksheet->getStyle('E'.$nextRow.':J'.$nextRow)->getFont()
            ->setItalic(true)
            ->setSize(9);

        // Generated by: [login or PIN staff name]
        ++$nextRow;
        self::setMinorText($worksheet, 'H'.$nextRow.':H'.($nextRow+2));
        $worksheet->setCellValue('H'.$nextRow, $translator->trans('generated_by', ['username'=>$user->getUsername()], Domain::INVOICE));
        // Generated on: [date]
        ++$nextRow;
        $worksheet->setCellValue('H'.$nextRow, $translator->trans('generated_on', ['date'=>time()], Domain::INVOICE));
        // Unique document integrity ID: BLANK
        ++$nextRow;
        $worksheet->setCellValue('H'.$nextRow, $translator->trans('checksum', ['checksum'=>''], Domain::INVOICE));

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

    private static function makeCommentedImportantInfo(string $importantInfo, string $commentInfo, ?string $color = null): RichText
    {
        $richText = new RichText();
        $importantText = $richText->createTextRun($importantInfo."\n");
        $importantText->getFont()
            ->setBold(true)
            ->setSize(15)
            ->setName('Arial');
        $comment = $richText->createTextRun($commentInfo);
        $comment->getFont()
            ->setBold(true)
            ->setSize(10)
            ->setName('Arial');
        if ($color) {
            $comment->getFont()->getColor()->setRGB($color);
            $importantText->getFont()->getColor()->setRGB($color);
        }
        return $richText;
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

        return $vendor->getLocation()->getCountryIso3();
    }
}
