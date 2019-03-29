<?php

namespace CommonBundle\Utils;

/**
 * Interface ExportableInterface
 * @package CommonBundle\Utils
 */
interface ExportableInterface
{

    /**
     * Returns an array representation of this class in order to prepare the export
     * @return array
     */
    public function getMappedValueForExport(): array;
}
