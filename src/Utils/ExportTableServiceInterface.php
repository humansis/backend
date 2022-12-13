<?php

declare(strict_types=1);

namespace Utils;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Interface ExportTableServiceInterface
 *
 * @package Utils
 */
interface ExportTableServiceInterface
{
    /**
     * return spreadsheet as StreamedResponse
     *
     * @param        $exportableTable
     * @param string $name
     * @param string $format
     * @param bool $headerDown
     * @param bool $headerBold
     * @param bool $headerFontItalic
     *
     * @return StreamedResponse
     */
    public function export(
        $exportableTable,
        string $name,
        string $format,
        bool $headerDown = false,
        bool $headerBold = false,
        bool $headerFontItalic = false
    ): StreamedResponse;
}
