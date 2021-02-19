<?php

declare(strict_types=1);

namespace DistributionBundle\Export;

use CommonBundle\Entity\Organization;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Translation\TranslatorInterface;
use VoucherBundle\Entity\SmartcardRedemptionBatch;

class SmartcardInvoiceExport
{
    /** @var TranslatorInterface */
    private $translator;

    /**
     * SmartcardInvoiceExport constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function export(SmartcardRedemptionBatch $batch, Organization $organization)
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $lang = 'en';

        self::formatCells($worksheet);
        self::buildHeader($worksheet, $this->translator, $lang, $organization, $batch);

        $lastRow = self::buildBody($worksheet, $this->translator, $lang, $batch);

        self::buildFooter($worksheet, $this->translator, $lang, $organization, $batch, ++$lastRow);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('invoice.xlsx');

        return 'invoice.xlsx';
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

    private static function buildHeader(Worksheet $worksheet, TranslatorInterface $translator, string $lang, Organization $organization, SmartcardRedemptionBatch $batch)
    {
        $worksheet->getRowDimension('2')->setRowHeight(24.02);
        $worksheet->getRowDimension('3')->setRowHeight(19.70);
        $worksheet->getRowDimension('5')->setRowHeight(26.80);
        $worksheet->getRowDimension('7')->setRowHeight(23.84);
        $worksheet->getRowDimension('8')->setRowHeight(17.36);
        $worksheet->getRowDimension('13')->setRowHeight(23.84);

        // Temporary Invoice No. box
        $worksheet->setCellValue('B2', $translator->trans('temporary_invoice_no', [], 'invoice', $lang));
        $worksheet->getStyle('B2:B3')->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyle('B2:B3')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Invoice No. box
        $worksheet->setCellValue('F2', $translator->trans('invoice_no', [], 'invoice', $lang));
        $worksheet->mergeCells('F2:H2');
        $worksheet->mergeCells('F3:H3');
        $worksheet->getStyle('F2:H3')->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyle('F2:H3')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // wide header "Invoice"
        $worksheet->mergeCells('B5:J5');
        $worksheet->setCellValue('B5', $translator->trans('invoice', [], 'invoice', $lang));
        $worksheet->getStyle('B5')->getFont()
            ->setBold(true)
            ->setSize(22)
            ->setName('Arial');
        $worksheet->getStyle('B5')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $worksheet->mergeCells('C7:D7');
        $worksheet->mergeCells('E7:G7');
        $worksheet->mergeCells('I7:J7');
        $worksheet->mergeCells('C8:G8');
        $worksheet->mergeCells('I8:J8');
        $worksheet->mergeCells('B9:B10');
        $worksheet->mergeCells('C9:E10');
        $worksheet->mergeCells('F9:G10');
        $worksheet->setCellValue('B7', $translator->trans('customer', [], 'invoice', $lang));
        $worksheet->setCellValue('C7', $organization->getName());
        $worksheet->setCellValue('B8', $translator->trans('supplier_name', [], 'invoice', $lang));
        $worksheet->setCellValue('C8', $batch->getVendor()->getName());
        $worksheet->setCellValue('H7', $translator->trans('invoice_date', [], 'invoice', $lang));
        $worksheet->setCellValue('I7', $batch->getRedeemedAt()->format('j.n.Y'));
        $worksheet->setCellValue('H8', $translator->trans('supplier_navi', [], 'invoice', $lang));
        $worksheet->setCellValue('B9', $translator->trans('contract_no', [], 'invoice', $lang));
        $worksheet->setCellValue('F9', $translator->trans('payment_method', [], 'invoice', $lang));
        $worksheet->setCellValue('H9', $translator->trans('cash', [], 'invoice', $lang));
        $worksheet->setCellValue('I9', $translator->trans('cheque', [], 'invoice', $lang));
        $worksheet->setCellValue('J9', $translator->trans('bank', [], 'invoice', $lang));
        $worksheet->getStyle('B7:B8')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle('B7:J10')->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyle('C7:E7')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('C5E0B4'));
        $worksheet->getStyle('C7:E7')->getFont()
            ->setBold(true)
            ->setSize(15)
            ->setName('Arial');
        $worksheet->getStyle('C8:C9')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('C5E0B4'));
        $worksheet->getStyle('C8:C9')->getFont()
            ->setBold(true)
            ->setSize(14)
            ->setName('Arial');
        $worksheet->getStyle('I7:I8')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('C5E0B4'));
        $worksheet->getStyle('I7')->getFont()
            ->setBold(true)
            ->setSize(12)
            ->setName('Arial');
        $worksheet->getStyle('H7:J10')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle('H10:J10')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('C5E0B4'));
        $worksheet->getStyle('B9:J10')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $worksheet->mergeCells('B13:C13');
        $worksheet->mergeCells('H13:I13');
        $worksheet->setCellValue('B13', $translator->trans('description', [], 'invoice', $lang));
        $worksheet->setCellValue('E13', $translator->trans('qty', [], 'invoice', $lang));
        $worksheet->setCellValue('F13', $translator->trans('unit', [], 'invoice', $lang));
        $worksheet->setCellValue('G13', $translator->trans('unit_price', [], 'invoice', $lang));
        $worksheet->setCellValue('H13', $translator->trans('amount', [], 'invoice', $lang));
        $worksheet->setCellValue('J13', $translator->trans('currency', [], 'invoice', $lang));
        $worksheet->getStyle('B13:J13')->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyle('B13:J13')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($organization->getLogo()) {
            $resource = imagecreatefrompng($organization->getLogo());

            $drawing = new MemoryDrawing();
            $drawing->setCoordinates('C2');
            $drawing->setImageResource($resource);
            $drawing->setRenderingFunction(MemoryDrawing::RENDERING_DEFAULT);
            $drawing->setMimeType(MemoryDrawing::MIMETYPE_DEFAULT);
            $drawing->setHeight(60);
            $drawing->setWorksheet($worksheet);
        }
    }

    private static function buildBody(Worksheet $worksheet, TranslatorInterface $translator, string $lang, SmartcardRedemptionBatch $batch)
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

        $currency = '';
        foreach ($batch->getPurchases() as $purchase) {
            $currency = $purchase->getSmartcard()->getCurrency();
            break;
        }

        $worksheet->setCellValue('B14', $translator->trans('redemption_payment', [], 'invoice', $lang));
        $worksheet->setCellValue('C14', $batch->getRedeemedAt()->format('d-m-Y'));
        $worksheet->setCellValue('F14', 'Cash');
        $worksheet->setCellValue('H14', sprintf('%.2f', $batch->getValue()));
        $worksheet->setCellValue('J14', $currency);

        return 14;
    }

    private static function buildFooter(Worksheet $worksheet, TranslatorInterface $translator, string $lang, Organization $organization, SmartcardRedemptionBatch $batch, $nextRow)
    {
        $currency = '';
        foreach ($batch->getPurchases() as $purchase) {
            $currency = $purchase->getSmartcard()->getCurrency();
            break;
        }

        $nextRow += 3;
        $worksheet->setCellValue('B'.$nextRow, $translator->trans('total_to_pay', [], 'invoice', $lang));
        $worksheet->setCellValue('H'.$nextRow, sprintf('%.2f', $batch->getValue()));
        $worksheet->setCellValue('J'.$nextRow, $currency);
        $worksheet->getRowDimension($nextRow)->setRowHeight(22.52);
        $worksheet->mergeCells('B'.$nextRow.':G'.$nextRow);
        $worksheet->mergeCells('H'.$nextRow.':I'.$nextRow);
        $worksheet->getStyle('B'.$nextRow.':G'.$nextRow)->getFont()
            ->setSize(15);
        $worksheet->getStyle('H'.$nextRow.':J'.$nextRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('C5E0B4'));
        $worksheet->getStyle('H'.$nextRow.':J'.$nextRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle('B'.$nextRow.':J'.$nextRow)->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_DOUBLE);

        $nextRow += 3;
        $worksheet->setCellValue('B'.$nextRow, $translator->trans('signature_recipient', [], 'invoice', $lang));
        $worksheet->mergeCells('B'.$nextRow.':D'.$nextRow);
        $worksheet->mergeCells('E'.$nextRow.':J'.$nextRow);
        $worksheet->getStyle('B'.$nextRow.':D'.$nextRow)->getFont()
            ->setSize(12);
        $worksheet->getStyle('B'.$nextRow.':D'.$nextRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle('E'.$nextRow.':J'.$nextRow)->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_DASHED);

        $nextRow += 2;
        $worksheet->setCellValue('B'.$nextRow, $organization->getName());
        $worksheet->mergeCells('B'.$nextRow.':D'.$nextRow);
        $worksheet->mergeCells('E'.$nextRow.':J'.$nextRow);
        $worksheet->getStyle('B'.$nextRow.':D'.$nextRow)->getFont()
            ->setSize(12);
        $worksheet->getStyle('B'.$nextRow.':D'.$nextRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle('E'.$nextRow.':J'.$nextRow)->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_DASHED);

        ++$nextRow;
        $worksheet->setCellValue('E'.$nextRow, $translator->trans('signature_organization', ['organization'=>$organization->getName()], 'invoice', $lang));
        $worksheet->mergeCells('E'.$nextRow.':J'.$nextRow);
        $worksheet->getStyle('E'.$nextRow.':J'.$nextRow)->getFont()
            ->setItalic(true)
            ->setSize(9);

        ++$nextRow;
        $worksheet->getStyle('B'.$nextRow.':J'.$nextRow)->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_DOUBLE);
    }
}
