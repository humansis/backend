<?php

namespace Utils;

use Exception\ExportNoDataException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;



class OpenSpoutExportService extends BasicExportService
{

    /**
     * Export spreadsheet to a file in . format (csv, xlsx, ods).
     *
     * @param         $exportableTable
     * @param String  $name
     * @param String  $format
     * @param bool    $headerDown
     * @param bool    $headerBold
     *
     * @return string $filename
     *
     * @throws ExportNoDataException
     */
    public function export(
        $exportableTable,
        string $name,
        string $format,
        bool $headerDown = false,
        bool $headerBold = false,
        bool $headerFontItalic = false
    ): String
    {
        if (0 === count($exportableTable)) {
            throw new ExportNoDataException('No data to export');
        }
        $filename = $this->generateSpreadsheet($exportableTable, $name, $format,$headerDown, $headerBold, $headerFontItalic);
        return $filename;
    }

    public function generateSpreadsheet($tableData, $name, $format, $headerDown, $headerBold, $headerFontItalic): string
    {
        $allrowsData = $this->normalize($tableData);
        $filename = $this->generateFile($name,$format);
        try {
            $this->writer->openToFile($filename);
        } catch (IOException $e) {
            throw new BadRequestHttpException("An error occurred while creating the file.");
        }
        $tableHeaders = $this->getHeader($allrowsData);
        $style_header = $this->getTheStyle($headerBold,$headerFontItalic);
        $style_row = $this->getTheStyle();
        $row_head = WriterEntityFactory::createRowFromArray($tableHeaders,$style_header);
        if ($headerDown === false) {
            try{
                $this->writer->addRow($row_head);
            }catch (IOException|WriterNotOpenedException $e) {
                return ($e->getMessage());
            }
        }
        foreach ($allrowsData as $rowData) {
            $row = WriterEntityFactory::createRowFromArray($rowData, $style_row);
            try{
                $this->writer->addRow($row);
            }catch (IOException|WriterNotOpenedException $e) {
                return ($e->getMessage());
            }
        }
        if ($headerDown === true) {
            try{
                $this->writer->addRow($row_head);
            }catch (IOException|WriterNotOpenedException $e) {
                return ($e->getMessage());
            }
        }
        $this->writer->close();
        return $filename;
    }



}
