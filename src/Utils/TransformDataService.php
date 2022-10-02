<?php declare(strict_types=1);

namespace Utils;

class TransformDataService
{
    /**
     * normalize data to become exportable in export service
     *
     * @param $data
     *
     * @return array
     */
    public function transform($data)
    {
        $exportableTable = [];

        foreach ($data as $value) {
            if ($value instanceof ExportableInterface) {
                $exportableTable[] = $value->getMappedValueForExport();
            } elseif (is_array($value)) {
                $exportableTable[] = $value;
            } else {
                throw new \InvalidArgumentException("The table to export contains a not allowed content ($value). Allowed content: array, ".ExportableInterface::class);
            }
        }

        return $exportableTable;
    }

}
