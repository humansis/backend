<?php declare(strict_types=1);

namespace Mapper\Import\Duplicity;

trait CompareTrait
{
    protected function compareScalarValue($databaseValue, $importValue): ?array
    {
        if ($databaseValue === $importValue) return null;
        return [
            'database' => $databaseValue,
            'import' => $importValue,
        ];
    }

    protected function compareLists(array $databaseValues, array $importValues): ?array
    {
        $data = [
            'same' => array_intersect($databaseValues, $importValues),
            'database' => array_diff($databaseValues, $importValues),
            'import' => array_diff($importValues, $databaseValues),
        ];
        if (empty($data['database']) && empty($data['import'])) return null;
        return $data;
    }
}
