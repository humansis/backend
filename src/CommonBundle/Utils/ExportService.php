<?php

namespace CommonBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ExportService.
 */
class ExportService
{
    const FORMAT_CSV = 'csv';
    const FORMAT_XLSX = 'xlsx';
    const FORMAT_ODS = 'ods';

    /** @var EntityManagerInterface */
    private $em;

    /** @var ContainerInterface */
    private $container;

    /** @var array An array that follows the csv format */
    private $headers;

    /**
     * ExportService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface     $container
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
    }

    /**
     * @param array $headers This array should follow the csv format
     *
     * @return ExportService
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

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
    public function generateFile(Spreadsheet $spreadsheet, string $name, string $type)
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
            return 'An error occured with the type file';
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
     *
     * @return string $filename
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function export($exportableTable, string $name, string $type)
    {
        if (0 === count($exportableTable)) {
            throw new \InvalidArgumentException('No data to export');
        }

        $rows = $this->normalize($exportableTable);

        $rowIndex = 1;

        $spreadsheet = new Spreadsheet();
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(0);
        $worksheet = $spreadsheet->getActiveSheet();

        $tableHeaders = $this->getHeaders($rows);
        $collumnIndexes = $this->generateColumnIndexes(count($tableHeaders));

        foreach ($tableHeaders as $i => $value) {
            $worksheet->setCellValue($collumnIndexes[$i].$rowIndex, $value);
        }

        foreach ($rows as $key => $value) {
            ++$rowIndex;
            foreach ($tableHeaders as $i => $header) {
                $worksheet->setCellValue($collumnIndexes[$i].$rowIndex, $value[$header]);
            }
        }

        return $this->generateFile($spreadsheet, $name, $type);
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
    private function getHeaders($exportableTable)
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
     * Returns list of excel columns index, eg. A, B, C, ..., AA, AB ...
     *
     * @param int $number
     *
     * @return string[]
     */
    public function generateColumnIndexes(int $number)
    {
        $result = [];

        $alphabetCount = ord('Z') - ord('A') + 1;
        $prefix = '';

        for ($i = 0; $i < $number; ++$i) {
            $it = floor($i / $alphabetCount);
            if ($it > 0) {
                $prefixChar = ord('A') + $it - 1;
                $prefix = chr($prefixChar);
            }

            $pos = $i % $alphabetCount;
            $char = ord('A') + $pos;

            $result[] = $prefix.chr($char);
        }

        return $result;
    }
}
