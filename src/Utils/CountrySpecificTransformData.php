<?php

declare(strict_types=1);

namespace Utils;

class CountrySpecificTransformData
{
    /**
     * Returns an array representation of Countries in order to prepare the export
     *
     * @param $countries
     *
     * @return array
     */
    public function transformData($countries): array
    {
        $exportableTable = [];

        foreach ($countries as $country) {
            $exportableTable [] = [
                "type" => $country->getType(),
                "Country Iso3" => $country->getCountryIso3(),
                "Field" => $country->getFieldString(),
            ];
        }

        return $exportableTable;
    }
}
