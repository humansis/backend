<?php

namespace Utils;

/**
 * Interface ExportableInterface
 * @package Utils
 */
interface ExportableInterface
{

    /**
     * Returns an array representation of this class in order to prepare the export
     * @return array
     */
    public function getMappedValueForExport(): array;
}
