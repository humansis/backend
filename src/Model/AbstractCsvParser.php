<?php

namespace Model;

use Exception\CsvParserException;
use Component\Assistance\Scoring\Model\ScoringRule;

abstract class AbstractCsvParser
{
    abstract protected function processCsv(array $csv);

    abstract protected function mandatoryColumns(): array;

    /**
     * @param string $pathToCsv
     *
     * @return mixed
     * @throws CsvParserException
     */
    public function parse(string $pathToCsv)
    {
        if (!file_exists($pathToCsv)) {
            throw new CsvParserException($pathToCsv, 'File not found');
        }

        $fileHandler = fopen($pathToCsv, 'r');
        if (false === $fileHandler) {
            throw new CsvParserException($pathToCsv, 'Failed to open file');
        }

        return $this->parseStream($fileHandler, $pathToCsv);
    }

    /**
     * @param        $csvStream
     * @param string $pathToCsv
     *
     * @return mixed
     * @throws CsvParserException
     */
    public function parseStream($csvStream, string $pathToCsv = 'streamed')
    {
        $csvHead = fgetcsv($csvStream);
        $this->checkMandatoryColumns($pathToCsv, $csvHead);
        $csv = [];
        while (false !== ($row = fgetcsv($csvStream))) {
            $trimmedRow = array_map(function (string $cell) {
                return trim($cell);
            }, $row);

            $csv[] = array_combine($csvHead, $trimmedRow);
        }

        fclose($csvStream);

        return $this->processCsv($csv);
    }

    /**
     * @param string $pathToCsv
     * @param array $firstRow
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
            throw new CsvParserException($pathToCsv, 'CSV file has wrong structure (missing columns ' . implode(', ', $missingColumns) . ' )');
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
