<?php

namespace Utils;

use Exception\ExportNoDataException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\WriterAbstract as Writer;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;



class OpenSpoutExportService extends BasicExportService
{
    const FLUSH_THRESHOLD = 100;

    /**
     * @var Writer
     */
    protected $writer;

    /**
     * Export spreadsheet to a file in . format (csv, xlsx, ods).
     *
     * @param         $exportableTable
     * @param String  $name
     * @param String  $format
     * @param bool    $headerDown
     * @param bool    $headerBold
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
    ): StreamedResponse
    {
        if (0 === count($exportableTable)) {
            throw new ExportNoDataException('No data to export');
        }
        $streamedResponse = $this->generateSpreadsheet($exportableTable, $name, $format,$headerDown, $headerBold, $headerFontItalic);
        return $streamedResponse;
    }

    public function generateSpreadsheet($tableData, $name, $format, $headerDown, $headerBold, $headerFontItalic): StreamedResponse
    {
        $allrowsData = $this->normalize($tableData);
        $filename = $this->generateFile($name,$format);
        $tableHeaders = $this->getHeader($allrowsData);
        $styleHeader = $this->getTheStyle($headerBold,$headerFontItalic);
        $styleRow = $this->getTheStyle();
        $rowHead = WriterEntityFactory::createRowFromArray($tableHeaders,$styleHeader);

        $streamedResponse = new StreamedResponse(function() use ($filename,$headerDown,$rowHead,$allrowsData,$styleRow) {
            try {
                $this->writer->openToFile("php://output");
            } catch (IOException $e) {
                throw new BadRequestHttpException("An error occurred while creating the file.");
            }
            if ($headerDown === false) {
                try {
                    $this->writer->addRow($rowHead);
                } catch (IOException|WriterNotOpenedException $e) {
                    return ($e->getMessage());
                }
            }
            $i = 0;
            foreach ($allrowsData as $rowData) {
                $row = WriterEntityFactory::createRowFromArray($rowData, $styleRow);
                try {
                    $this->writer->addRow($row);
                } catch (IOException|WriterNotOpenedException $e) {
                    return ($e->getMessage());
                }
                $i++;
                // Flushing the buffer every N rows to stream echo'ed content.
                if ($i % self::FLUSH_THRESHOLD === 0) {
                    flush();
                }
            }
            if ($headerDown === true) {
                try {
                    $this->writer->addRow($rowHead);
                } catch (IOException|WriterNotOpenedException $e) {
                    return ($e->getMessage());
                }
            }
            $this->writer->close();
        });

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        );
        $streamedResponse->headers->set('Content-Disposition', $disposition);
        return $streamedResponse;
    }



}
