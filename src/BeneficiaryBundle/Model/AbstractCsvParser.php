<?php

namespace BeneficiaryBundle\Model;

use BeneficiaryBundle\Exception\CsvParserException;

abstract class AbstractCsvParser
{
    protected $mandatoryColumns = [];

    abstract public function parse(string $pathToCsv);

    /**
     * @param string $pathToCsv
     *
     * @return array
     *
     * @throws CsvParserException
     */
    protected function readCsvToArray(string $pathToCsv): array
    {
        if (!file_exists($pathToCsv)) {
            throw new CsvParserException('File not found ('.$pathToCsv.')');
        }

        $fileHandler = fopen($pathToCsv, 'r');
        if (false === $fileHandler) {
            throw new CsvParserException('Failed to open file ('.realpath($pathToCsv).')');
        }

        $csvHead = (array) fgetcsv($fileHandler);

        $this->checkMandatoryColumns($csvHead);

        $csv = [];
        while (false !== ($row = fgetcsv($fileHandler))) {
            $trimmedRow = array_map(function (string $cell) {
                return trim($cell);
            }, $row);

            $csv[] = array_combine($csvHead, $trimmedRow);
        }

        fclose($fileHandler);

        return $csv;
    }

    /**
     * @param array $firstRow
     *
     * @throws CsvParserException
     */
    private function checkMandatoryColumns(array $firstRow): void
    {
        $missingColumns = [];

        foreach ($this->mandatoryColumns as $column) {
            if (!in_array($column, $firstRow)) {
                $missingColumns[] = $column;
            }
        }

        if (!empty($missingColumns)) {
            throw new CsvParserException('CSV file has wrong structure (missing columns '.implode(', ', $missingColumns).' )');
        }
    }


    protected function rowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (!empty($cell)) {
                return false;
            }
        }

        return true;
    }
}
