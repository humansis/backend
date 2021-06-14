<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use NewApiBundle\Component\Import\Exception\InvalidImportException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\File\File;

class ImportParser
{
    private const HEADER_ROW = 1; // header definition is at row #1
    private const CONTENT_ROW = 6; // content starts at row #5

    /**
     * @param File $file
     *
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws InvalidImportException
     */
    public function parse(File $file)
    {
        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true);

        $worksheet = $reader->load($file->getRealPath())->getActiveSheet();

        $headers = $this->getHeaders($worksheet);

        if (!in_array('Head', $headers)) {
            throw new InvalidImportException('File does not contains required column Head');
        }

        $list = [];
        $household = [];
        for ($r = self::CONTENT_ROW; ; $r++) {
            $row = $this->getRow($worksheet, $headers, $r);
            if (-1 === $row) {
                break;
            }

            if ('true' === strtolower($row['Head'])) {
                if ([] !== $household) {
                    // everytime new household head is found, previous HH is added to list
                    $list[] = $household;
                }

                $household = [$row];
            } else {
                $household[] = $row;
            }
        }

        // in the end, last household is also added to list
        $list[] = $household;

        return $list;
    }

    /**
     * @param Worksheet $worksheet
     *
     * @return array
     */
    private function getHeaders(Worksheet $worksheet): array
    {
        $headers = [];

        for ($i = self::HEADER_ROW; ; $i++) {
            $cell = $worksheet->getCellByColumnAndRow($i, 1, false);
            $value = self::value($cell);

            if (empty($value)) {
                break;
            }

            $headers[$i] = $value;
        }

        return $headers;
    }

    /***
     * @param Worksheet $worksheet
     * @param array     $headers
     * @param int       $r row number
     *
     * @return array|int -1 if end of file, data of row otherwise
     */
    private function getRow(Worksheet $worksheet, array $headers, int $r)
    {
        $row = [];
        $stop = true;

        for ($c = 1; $c <= count($headers); $c++) {
            $cell = $worksheet->getCellByColumnAndRow($c, $r, false);
            $value = self::value($cell);

            $header = $headers[$c];
            $row[$header] = $value;

            $stop &= empty($value);
        }

        if ($stop) {
            return -1;
        }

        return $row;
    }

    /**
     * @param Cell|null $cell
     *
     * @return mixed
     */
    private static function value(?Cell $cell)
    {
        if ($cell) {
            return is_string($cell->getValue()) ? trim($cell->getValue()) : $cell->getValue();
        }

        return null;
    }
}
