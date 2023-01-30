<?php

declare(strict_types=1);

namespace Utils;

class CountrySpecificTransformData
{
    /**
     * Returns an array representation of Countries in order to prepare the export
     */
    public function transformData(array $countrySpecifics): array
    {
        $exportableTable = [];

        foreach ($countrySpecifics as $object) {
            $exportableTable [] = [
                "type" => $object->getType(),
                "Country Iso3" => $object->getCountryIso3(),
                "Field" => $object->getFieldString(),
            ];
        }

        return $exportableTable;
    }
}
