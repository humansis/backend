<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\InvalidCell;

use NewApiBundle\Component\Import\CellParameters;
use NewApiBundle\Component\Import\InvalidCell\ColumnSpecific\ColumnSpecific;
use NewApiBundle\Component\Import\InvalidCell\ColumnSpecific\HeadSpecific;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

final class InvalidCell
{
    private const FORMULA_ERROR_CELL_VALUE = 'INVALID VALUE: formulas are not supported, please fill a value';

    private const COLUMNS_SPECIFIC = [
        HeadSpecific::class,
    ];

    /**
     * @var string
     */
    private $columnName;

    /**
     * @var float|int|string
     */
    private $cellValue;

    /**
     * @var string
     */
    private $cellDataType;

    /**
     * @var array|null
     */
    private $cellErrors;

    /**
     * @var ColumnSpecific[]
     */
    private $columnsSpecific = [];

    /**
     * @param string           $columnName
     * @param string|int|float $cellValue
     * @param string           $cellDataType
     * @param array|null       $cellErrors
     */
    public function __construct(
        string $columnName,
               $cellValue,
        string $cellDataType,
        ?array $cellErrors = null
    ) {
        $this->columnName = $columnName;
        $this->cellValue = $cellValue;
        $this->cellDataType = $cellDataType;
        $this->cellErrors = $cellErrors;

        foreach (self::COLUMNS_SPECIFIC as $columnSpecific) {

            /** @var ColumnSpecific $columnSpecificClass */
            $columnSpecificClass = new $columnSpecific();
            $this->columnsSpecific[$columnSpecificClass->getColumn()] = $columnSpecificClass;
        }

        $this->preventFormulaError();
        $this->callCustomValueCallback();
    }

    /**
     * @return float|int|string
     */
    public function getCellValue()
    {
        return $this->cellValue;
    }

    /**
     * @return string
     */
    public function getCellDataType(): string
    {
        return $this->cellDataType;
    }

    private function callCustomValueCallback(): void
    {
        if (array_key_exists($this->columnName, $this->columnsSpecific)) {
            $fn = $this->columnsSpecific[$this->columnName]->getValueCallback();
            $this->cellValue = $fn($this->cellValue, $this->cellDataType);
        }
    }

    private function preventFormulaError(): void
    {
        if ($this->cellDataType === DataType::TYPE_FORMULA && $this->cellErrors && array_key_exists(CellParameters::ERRORS, $this->cellErrors)) {
            $this->cellDataType = DataType::TYPE_STRING;
            $this->cellValue = self::FORMULA_ERROR_CELL_VALUE;
        }
    }

}
