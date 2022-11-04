<?php

declare(strict_types=1);

namespace Component\Import\InvalidCell;

use Component\Import\CellParameters;
use Component\Import\InvalidCell\ColumnSpecific\ColumnSpecific;
use Component\Import\InvalidCell\ColumnSpecific\HeadSpecific;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

final class InvalidCell
{
    private const FORMULA_ERROR_CELL_VALUE = 'INVALID VALUE: formulas are not supported, please fill a value';
    private const COLUMNS_SPECIFIC = [
        HeadSpecific::class,
    ];

    /**
     * @var ColumnSpecific[]
     */
    private array $columnsSpecific = [];

    public function __construct(
        private readonly string $columnName,
        private float|int|string|null $cellValue,
        private string $cellDataType,
        private readonly ?array $cellErrors = null
    ) {
        foreach (self::COLUMNS_SPECIFIC as $columnSpecific) {
            /** @var ColumnSpecific $columnSpecificClass */
            $columnSpecificClass = new $columnSpecific();
            $this->columnsSpecific[$columnSpecificClass->getColumn()] = $columnSpecificClass;
        }

        $this->preventFormulaError();
        $this->callCustomValueCallback();
    }

    public function getCellValue(): float|int|string|null
    {
        return $this->cellValue;
    }

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
        if (
            $this->cellDataType === DataType::TYPE_FORMULA && $this->cellErrors && array_key_exists(
                CellParameters::ERRORS,
                $this->cellErrors
            )
        ) {
            $this->cellDataType = DataType::TYPE_STRING;
            $this->cellValue = self::FORMULA_ERROR_CELL_VALUE;
        }
    }
}
