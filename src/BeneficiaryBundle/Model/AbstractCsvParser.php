<?php

namespace BeneficiaryBundle\Model;

use BeneficiaryBundle\Exception\CsvParserException;

abstract class AbstractCsvParser
{
    abstract protected function processCsv(array $csv);

    abstract protected function mandatoryColumns(): array;


    /**
     * @param string $pathToCsv
     *
     * @throws CsvParserException
     */
    public function parse(string $pathToCsv)
    {
        if (!file_exists($pathToCsv)) {
            throw new CsvParserException($pathToCsv,'File not found');
        }

        $fileHandler = fopen($pathToCsv, 'r');
        if (false === $fileHandler) {
            throw new CsvParserException($pathToCsv, 'Failed to open file');
        }

        $csvHead = (array) fgetcsv($fileHandler);

        $this->checkMandatoryColumns($pathToCsv, $csvHead);

        $csv = [];
        while (false !== ($row = fgetcsv($fileHandler))) {
            $trimmedRow = array_map(function (string $cell) {
                return trim($cell);
            }, $row);

            $csv[] = array_combine($csvHead, $trimmedRow);
        }

        fclose($fileHandler);

        return $this->processCsv($csv);
    }


    /**
     * @param string $pathToCsv
     * @param array  $firstRow
     *
     * @throws CsvParserException
     */
    private function checkMandatoryColumns(string $pathToCsv, array $firstRow): void
    {
        $mandatoryColumns = $this->mandatoryColumns();
        $missingColumns = [];

        foreach ($mandatoryColumns as $column) {
            if (!in_array($column, $firstRow)) {
                $missingColumns[] = $column;
            }
        }

        if (!empty($missingColumns)) {
            throw new CsvParserException($pathToCsv, 'CSV file has wrong structure (missing columns '.implode(', ', $missingColumns).' )');
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
