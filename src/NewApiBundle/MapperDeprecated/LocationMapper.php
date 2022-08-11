<?php
namespace NewApiBundle\MapperDeprecated;

use NewApiBundle\Entity\Location;

class LocationMapper
{
    public function toFlatArray(?Location $location): ?array
    {
        if (!$location) return null;
        return $this->expandLocation($location);
    }

    private function expandLocation(Location $location): array
    {
        $ids = [
            'adm1' => null,
            'adm2' => null,
            'adm3' => null,
            'adm4' => null,
        ];

        while ($location !== null) {
            $ids['adm' . $location->getLvl()] = $location->getId();
            $location = $location->getParent();
        }
        
        return $ids;
    }

    public function toName(Location $location): string
    {
        $names = [];

        while ($location !== null) {
            $names[] = $location->getName();
            $location = $location->getParent();
        }
        
        return implode(', ', $names);
    }


}
