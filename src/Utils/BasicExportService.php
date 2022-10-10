<?php

declare(strict_types=1);

namespace Utils;

use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;

class BasicExportService
{
    public const FORMAT_CSV = 'csv';
    public const FORMAT_XLSX = 'xlsx';
    public const FORMAT_ODS = 'ods';

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
}
