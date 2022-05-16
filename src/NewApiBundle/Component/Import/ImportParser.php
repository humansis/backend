<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use NewApiBundle\Component\Import\CellError\ErrorTypes;
use NewApiBundle\Component\Import\Exception\InvalidFormulaException;
use NewApiBundle\Component\Import\Exception\InvalidImportException;
use NewApiBundle\Enum\EnumValueNoFoundException;
use NewApiBundle\Enum\HouseholdHead;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\File\File;

class ImportParser
{
    private const VERSION_1 = 1; //internal versions marking
    private const VERSION_1_SRC = ""; //version within source datasheet (not relevant for version 1, versioning starts from version 2)

    private const VERSION_2 = 2; //internal versions marking
    private const VERSION_2_SRC = "2.0"; //version within source datasheet

    private const VERSION_COLUMN = 1; //position in the source datasheet (xls, ...)
    private const VERSION_ROW = 4; //position in the source datasheet (xls, ...)

    private const HEADER_ROW = 0;
    private const HEADER_COLUMN = 1;
    private const CONTENT_ROW = 2;
    private const CONTENT_COLUMN = 3;

    private $versionCustomValues = [
        self::VERSION_1 => [
            self::HEADER_ROW => 1, //header is at row #1
            self::HEADER_COLUMN => 1, //header starts at column #1
            self::CONTENT_ROW => 6, //content starts at row #6
            self::CONTENT_COLUMN => 1, //content starts at column #1
        ],

        self::VERSION_2 => [
            self::HEADER_ROW => 5, //header is at row #5
            self::HEADER_COLUMN => 1, //header starts at column #3
            self::CONTENT_ROW => 6, //content starts at row #6
            self::CONTENT_COLUMN => 1, //content starts at column #3
        ]
    ];

    /**
     * @param File $file
     *
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws InvalidImportException
     * @throws InvalidFormulaException
     */
    public function parse(File $file): array
    {
        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $worksheet = $reader->load($file->getRealPath())->getActiveSheet();
        $headers = $this->getHeader($worksheet);

        $list = [];
        $household = [];
        $startContentRow = $this->getStartContentRow($worksheet);

        for ($r = $startContentRow; ; $r++) {
            $row = $this->getContentRow($worksheet, $headers, $r);
            if (-1 === $row) {
                break;
            }

            // null => member
            try{
                $isHead = HouseholdHead::valueFromAPI($row['Head'][CellParameters::VALUE]);
            }catch (EnumValueNoFoundException $exception){
                $isHead = false;
            }

            if (true === $isHead) {
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
     * @param File $file
     *
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception|InvalidFormulaException
     */
    public function parseHeadersOnly(File $file): array
    {
        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true);

        $worksheet = $reader->load($file->getRealPath())->getActiveSheet();

        return $this->getHeader($worksheet);
    }

    /**
     * @param Worksheet $worksheet
     *
     * @return array
     * @throws InvalidFormulaException
     */
    private function getHeader(Worksheet $worksheet): array
    {
        $startHeaderRow = $this->getStartHeaderRow($worksheet);
        $startHeaderColumn = $this->getStartHeaderColumn($worksheet);

        $headers = [];

        for ($headerColumn = $startHeaderColumn; ; $headerColumn++) {
            $cell = $worksheet->getCellByColumnAndRow($headerColumn, $startHeaderRow, false);
            $value = self::value($cell);

            if (empty($value)) {
                break;
            }

            $headers[$headerColumn] = $value;
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
    private function getContentRow(Worksheet $worksheet, array $headers, int $r)
    {
        $row = [];
        $stop = true;
        $startContentColumn = $this->getStartContentColumn($worksheet);

        for ($c = $startContentColumn; $c <= count($headers); $c++) {
            $cellError = null;
            $cell = $worksheet->getCellByColumnAndRow($c, $r, false);
            try {
                $value = self::value($cell);
            } catch (InvalidFormulaException $e) {
                $value = $e->getFormula();
                $cellError = ErrorTypes::FORMULA_ERROR;
            }

            $header = $headers[$c];
            if ($cell) {
                $dataType = $cell->getDataType();

                // convert formula type to string|number type
                if ($dataType === DataType::TYPE_FORMULA && !$cellError) {
                    $dataType = is_numeric($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING;
                }
                $valueData = [
                    CellParameters::VALUE => $value,
                    CellParameters::DATA_TYPE => $dataType,
                    CellParameters::NUMBER_FORMAT => $cell->getStyle()->getNumberFormat()->getFormatCode(),
                ];
                if ($cellError) {
                    $valueData[CellParameters::ERRORS] = $cellError;
                }
            } else {
                $valueData = null;
            }

            $row[$header] = $valueData;
            $stop &= empty($value);
        }

        if ($stop) {
            return -1;
        }

        return $row;
    }

    private function getTemplateVersion($worksheet): int
    {
        $versionRawValue = $worksheet->getCellByColumnAndRow(self::VERSION_COLUMN, self::VERSION_ROW, false);

        switch ($versionRawValue) {
            case self::VERSION_2_SRC:
                $version = self::VERSION_2;
                break;
            default:
                $version = self::VERSION_1;
        }

        return $version;
    }

    private function getStartContentColumn($worksheet): int
    {
        $version = $this->getTemplateVersion($worksheet);
        return $this->versionCustomValues[$version][self::CONTENT_COLUMN];
    }

    private function getStartContentRow($worksheet): int
    {
        $version = $this->getTemplateVersion($worksheet);
        return $this->versionCustomValues[$version][self::CONTENT_ROW];
    }

    private function getStartHeaderColumn($worksheet): int
    {
        $version = $this->getTemplateVersion($worksheet);
        return $this->versionCustomValues[$version][self::HEADER_COLUMN];
    }

    private function getStartHeaderRow($worksheet): int
    {
        $version = $this->getTemplateVersion($worksheet);
        return $this->versionCustomValues[$version][self::HEADER_ROW];
    }

        /**
     * @param Cell|null $cell
     *
     * @return mixed
     * @throws InvalidFormulaException
     */
    private static function value(?Cell $cell)
    {
        if ($cell) {
            try {
                $calculatedValue = $cell->getCalculatedValue();
                if (is_string($calculatedValue)) {
                    $value = trim($calculatedValue);

                    // prevent bad formatted spreadsheet cell starting with apostrophe
                    if (strpos($value, '\'') === 0) {
                        $value = substr_replace($value, '', 0, 1);
                    }
                } else {
                    $value = $calculatedValue;
                }
            } catch (\PhpOffice\PhpSpreadsheet\Calculation\Exception $exception) {
                throw new InvalidFormulaException($cell->getValue(), "Bad formula at cell {$cell->getColumn()}{$cell->getRow()}");
            }

            return $value;
        }

        return null;
    }
}
