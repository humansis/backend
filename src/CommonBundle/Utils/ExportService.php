<?php

namespace CommonBundle\Utils;

use BeneficiaryBundle\Utils\ExcelColumnsGenerator;
use CommonBundle\Utils\Exception\ExportNoDataException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Class ExportService.
 */
class ExportService
{
    const FORMAT_CSV = 'csv';
    const FORMAT_XLSX = 'xlsx';
    const FORMAT_ODS = 'ods';

    /**
     * Generate file.
     *
     * @param Spreadsheet $spreadsheet
     * @param string      $name
     * @param string      $type
     *
     * @return string $filename
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function generateFile(Spreadsheet $spreadsheet, string $name, string $type): string
    {
        if (self::FORMAT_CSV == $type) {
            $writer = IOFactory::createWriter($spreadsheet, 'Csv');
            $writer->setEnclosure('');
            $writer->setDelimiter(';');
            $writer->setUseBOM(true);
            $filename = $name.'.csv';
        } elseif (self::FORMAT_XLSX == $type) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $filename = $name.'.xlsx';
        } elseif (self::FORMAT_ODS == $type) {
            $writer = IOFactory::createWriter($spreadsheet, 'Ods');
            $filename = $name.'.ods';
        } else {
            return 'An error occured with the type file: '.$type;
        }

        $writer->save($filename);

        return $filename;
    }

    /**
     * Export data to file (csv, xlsx, ods).
     *
     * @param        $exportableTable
     * @param string $name
     * @param string $type
     * @param bool   $headerDown
     *
     * @return string $filename
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws ExportNoDataException
     */
    public function export(
        $exportableTable,
        string $name,
        string $type,
        bool $headerDown = false,
        bool $headerBold = false
    ): string {
        if (0 === count($exportableTable)) {
            throw new ExportNoDataException('No data to export');
        }

        $spreadsheet = $this->generateSpreadsheet($exportableTable, $headerDown, $headerBold);

        return $this->generateFile($spreadsheet, $name, $type);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generateSpreadsheet($tableData, bool $headerDown = true, bool $headerBold = false): Spreadsheet
    {
        $rows = $this->normalize($tableData);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(0);
        $worksheet = $spreadsheet->getActiveSheet();

        $tableHeaders = $this->getHeader($rows);
        $generator = new ExcelColumnsGenerator();

        $rowIndex = 1;
        if ($headerDown === false) {
            $this->generateHeader($worksheet, $tableHeaders, $generator, $rowIndex, $headerBold);
            $rowIndex = 2;
            $this->generateData($worksheet, $tableHeaders, $generator, $rowIndex, $rows);
        } else {
            $this->generateData($worksheet, $tableHeaders, $generator, $rowIndex, $rows);
            $this->generateHeader($worksheet, $tableHeaders, $generator, $rowIndex, $headerBold);
        }

        return $spreadsheet;
    }

    private function generateHeader($worksheet, $tableHeaders, $generator, $rowIndex, bool $headerBold)
    {
        $generator->reset();
        foreach ($tableHeaders as $value) {
            $cellCoords = $generator->getNext().$rowIndex;
            $worksheet->setCellValue($cellCoords, $value);
            $worksheet->getStyle($cellCoords)->getFont()->setBold($headerBold);
        }
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function generateData($worksheet, $tableHeaders, $generator, &$rowIndex, $rows)
    {
        foreach ($rows as $value) {
            $generator->reset();
            foreach ($tableHeaders as $header) {
                $cellCoords = $generator->getNext().$rowIndex;
                /**
                 * @var Cell $cell
                 */
                $cell = $worksheet->getCell($cellCoords);
                $dataToWrite = $value[$header] ?? null;

                if ($dataToWrite instanceof Hyperlink) {
                    $url = $dataToWrite->getUrl();
                    $toolTip = $dataToWrite->getTooltip();
                    $cell->setValue('=Hyperlink("'.$url.'","'.$toolTip.'")');
                } else {
                    $cell->setValue($dataToWrite);
                }
            }
            ++$rowIndex;
        }
    }

    /**
     * Export two-dimension array to file (csv, xlsx, ods).
     *
     * @param array[] $exportTable
     * @param string  $name
     * @param string  $type
     *
     * @return string $filename
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportRaw(array $exportTable, string $name, string $type)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($exportTable, null, 'A1');
        $filename = $this->generateFile($spreadsheet, $name, $type);

        return $filename;
    }

    /**
     * @param $exportableTable
     *
     * @return array
     */
    private function normalize($exportableTable)
    {
        $normalizedTable = [];

        foreach ($exportableTable as $value) {
            if ($value instanceof ExportableInterface) {
                $normalizedTable[] = $value->getMappedValueForExport();
            } elseif (is_array($value)) {
                $normalizedTable[] = $value;
            } else {
                throw new \InvalidArgumentException("The table to export contains a not allowed content ($value). Allowed content: array, ".ExportableInterface::class);
            }
        }

        return $normalizedTable;
    }

    /**
     * Return list of header names.
     *
     * We get all the keys that will become the column names for the csv.
     * We merge the results because some rows can have more or less columns
     *
     * @param array $exportableTable
     *
     * @return array list of all headers of exported table
     */
    private function getHeader($exportableTable)
    {
        $headers = [];

        foreach ($exportableTable as $row) {
            foreach ($row as $key => $value) {
                $headers[$key] = true;
            }
        }

        return array_keys($headers);
    }
}
