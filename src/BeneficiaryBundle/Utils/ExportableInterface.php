<?php

namespace BeneficiaryBundle\Utils;


interface ExportableInterface {

    /**
     * Returns an array representation of this class in order to prepare the export
     * @return array
     */
    function getMappedValueForExport(): array;

}