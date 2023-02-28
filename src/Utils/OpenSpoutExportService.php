<?php

declare(strict_types=1);

namespace Utils;

use Exception;
use Exception\ExportErrorException;
use Exception\ExportNoDataException;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\WriterAbstract as Writer;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OpenSpoutExportService extends BasicExportService implements ExportTableServiceInterface
{
    /**
     * @var int
     */
    public const FLUSH_THRESHOLD = 100;
    public const EXPORT_LIMIT = 10000;
    public const EXPORT_LIMIT_CSV = 20000;

    /**
     * Export spreadsheet to a file in . format (csv, xlsx, ods).
     *
     * @param         $exportableTable
     * @param String $name
     * @param String $format
     * @param bool $headerDown
     * @param bool $headerBold
     *
     * @return StreamedResponse $streamedResponse
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
    ): StreamedResponse {
        if ($format !== 'csv' && count($exportableTable) > self::EXPORT_LIMIT) {
            $count = count($exportableTable);
            throw new BadRequestHttpException("Too much records ($count) to export. Limit is " . self::EXPORT_LIMIT);
        } elseif ($format == 'csv' && count($exportableTable) > self::EXPORT_LIMIT_CSV) {
            $count = count($exportableTable);
            throw new BadRequestHttpException(
                "Too much records ($count) to export. Limit is for CSV is " . self::EXPORT_LIMIT_CSV
            );
        } elseif (0 === count($exportableTable)) {
            throw new ExportNoDataException('No data to export');
        }

        return $this->generateSpreadsheet(
            $exportableTable,
            $name,
            $format,
            $headerDown,
            $headerBold,
            $headerFontItalic
        );
    }

    public function generateSpreadsheet(
        $tableData,
        $name,
        $format,
        $headerDown,
        $headerBold,
        $headerFontItalic
    ): StreamedResponse {
        $writer = $this->createWriter($format);
        $filename = $this->generateFileName($name, $format);
        $tableHeaders = $this->getHeader($tableData);
        $styleHeader = $this->getTheStyle($headerBold, $headerFontItalic);
        $styleRow = $this->getTheStyle();
        $rowHead = WriterEntityFactory::createRowFromArray($tableHeaders, $styleHeader);

        $streamedResponse = new StreamedResponse(
            function () use ($writer, $headerDown, $rowHead, $tableData, $styleRow) {
                try {
                    $writer->openToFile("php://output");

                    if ($headerDown === false) {
                        $writer->addRow($rowHead);
                    }
                    $i = 0;
                    foreach ($tableData as $rowData) {
                        $row = WriterEntityFactory::createRowFromArray($rowData, $styleRow);
                        $writer->addRow($row);
                        $i++;
                        // Flushing the buffer every N rows to stream echo'ed content.
                        if ($i % self::FLUSH_THRESHOLD === 0) {
                            flush();
                        }
                    }
                    if ($headerDown === true) {
                        $writer->addRow($rowHead);
                    }
                    $writer->close();
                } catch (Exception $ex) {
                    throw new ExportErrorException(
                        'An error occurred while exporting the file. {' . $ex->getMessage() . '}'
                    );
                }
            }
        );

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        );
        $streamedResponse->headers->set('Content-Disposition', $disposition);

        return $streamedResponse;
    }

    /**
     * @param string $type
     *
     * @return Writer
     */
    public function createWriter(string $type): Writer
    {
        if (self::FORMAT_CSV == $type) {
            $writer = WriterEntityFactory::createCSVWriter();
        } elseif (self::FORMAT_XLSX == $type) {
            $writer = WriterEntityFactory::createXLSXWriter();
        } elseif (self::FORMAT_ODS == $type) {
            $writer = WriterEntityFactory::createODSWriter();
        } else {
            throw new BadRequestHttpException('An error occurred with the type file: ' . $type);
        }

        return $writer;
    }

    /**
     * @param string $name
     * @param string $type
     *
     * @return string
     */
    public function generateFileName(string $name, string $type): string
    {
        if (self::FORMAT_CSV == $type) {
            $filename = $name . '.csv';
        } elseif (self::FORMAT_XLSX == $type) {
            $filename = $name . '.xlsx';
        } elseif (self::FORMAT_ODS == $type) {
            $filename = $name . '.ods';
        } else {
            throw new BadRequestHttpException('An error occurred with the type file: ' . $type);
        }

        return $filename;
    }
}
