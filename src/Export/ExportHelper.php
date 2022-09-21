<?php declare(strict_types=1);

namespace Export;

use Entity\Assistance;

class ExportHelper
{
    public const EMPTY_VALUE = 'N/A';

    public static function getLocationTreeNames(Assistance $assistance): array
    {
        $location = $assistance->getLocation();
        $names = array_fill(0, 4 , null);

        while ($location) {
            $names[$location->getLvl() - 1] = $location->getName();
            $location = $location->getParent();
        }

        return $names;
    }

}
