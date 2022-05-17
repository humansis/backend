<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Utils;

use NewApiBundle\Component\Import\ValueObject\ListCompare;
use NewApiBundle\Component\Import\ValueObject\ScalarCompare;

trait CompareTrait
{
    protected function compareScalarValue($databaseValue, $importValue): ?ScalarCompare
    {
        if ($databaseValue === $importValue) return null;
        return new ScalarCompare($importValue, $databaseValue);
    }

    protected function compareEnum(string $enumClass, $databaseValue, $importValue): ?ScalarCompare
    {
        if ($databaseValue === $importValue) return null;
        return new ScalarCompare(
            $importValue ? $enumClass::valueToAPI($importValue) : null,
            $databaseValue ? $enumClass::valueToAPI($databaseValue) : null
        );
    }

    protected function compareLists(array $databaseValues, array $importValues): ?ListCompare
    {
        $data = [
            'same' => array_intersect($databaseValues, $importValues),
            'database' => array_diff($databaseValues, $importValues),
            'import' => array_diff($importValues, $databaseValues),
        ];
        if (empty($data['database']) && empty($data['import'])) return null;
        return new ListCompare($data['import'], $data['database'], $data['same']);
    }
}
