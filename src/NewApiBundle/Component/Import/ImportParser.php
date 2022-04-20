<?php declare(strict_types=1);

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

        $worksheet = $reader->load($file->getRealPath())->getActiveSheet();

        $headers = $this->getHeaders($worksheet);

        $list = [];
        $household = [];
        for ($r = self::CONTENT_ROW; ; $r++) {
            $row = $this->getRow($worksheet, $headers, $r);
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
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function parseHeadersOnly(File $file): array
    {
        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true);

        $worksheet = $reader->load($file->getRealPath())->getActiveSheet();

        return $this->getHeaders($worksheet);
    }

    /**
     * @param Worksheet $worksheet
     *
     * @return array
     * @throws InvalidFormulaException
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
