<?php

namespace Utils;

use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\Exception\WriterAlreadyOpenedException;
use OpenSpout\Writer\WriterAbstract as Writer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BasicExportService
{
    const FORMAT_CSV = 'csv';
    const FORMAT_XLSX = 'xlsx';
    const FORMAT_ODS = 'ods';


    /**
     * @var Writer
     */
    protected $writer;

    /**
     * @param $exportableTable
     *
     * @return array
     */
    public function normalize($exportableTable)
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
    public function getHeader($exportableTable)
    {
        $headers = [];

        foreach ($exportableTable as $row) {
            foreach ($row as $key => $value) {
                $headers[$key] = true;
            }
        }

        return array_keys($headers);
    }

    /**
     * It is possible to customize the style we need for the row and cell.
     *
     * @param bool $isBold
     * @param bool $isItalic
     *
     * @return Style
     */
    public function getTheStyle(bool $isBold = false, bool $isItalic = false): Style
    {
        $style = new Style();
        $style->setFontColor(Color::BLACK);
        if ($isBold) {
            $style->setFontBold();
        }
        if ($isItalic) {
            $style->setFontItalic();
        }
        return $style;
    }

    /**
     * Generate file.
     *
     * @param string $name
     * @param string $type
     *
     * @return string $filename
     * @throws WriterAlreadyOpenedException
     */
    public function generateFile(string $name, string $type): string
    {
        if (self::FORMAT_CSV == $type) {
            $this->writer = WriterEntityFactory::createCSVWriter();
            $filename = $name.'.csv';
        } elseif (self::FORMAT_XLSX == $type) {
            $this->writer = WriterEntityFactory::createXLSXWriter();
            $this->writer->setShouldUseInlineStrings(true);
            $filename = $name.'.xlsx';
        } elseif (self::FORMAT_ODS == $type) {
            $this->writer = WriterEntityFactory::createODSWriter();
            $filename = $name.'.ods';
        } else {
            throw new BadRequestHttpException('An error occurred with the type file: '.$type);
        }
        return $filename;
    }
}
