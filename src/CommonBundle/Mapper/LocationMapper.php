<?php
namespace CommonBundle\Mapper;

use CommonBundle\Entity\Location;

class LocationMapper
{
    public function toFlatArray(?Location $location): ?array
    {
        if (!$location) return null;
        return $this->expandLocation($location);
    }

    private function expandLocation(Location $location): array
    {
        if ($location->getAdm4()) {
            return [
                'adm1' => $location->getAdm4()->getAdm3()->getAdm2()->getAdm1()->getId(),
                'adm2' => $location->getAdm4()->getAdm3()->getAdm2()->getId(),
                'adm3' => $location->getAdm4()->getAdm3()->getId(),
                'adm4' => $location->getAdm4()->getId(),
            ];
        }
        if ($location->getAdm3()) {
            return [
                'adm1' => $location->getAdm3()->getAdm2()->getAdm1()->getId(),
                'adm2' => $location->getAdm3()->getAdm2()->getId(),
                'adm3' => $location->getAdm3()->getId(),
                'adm4' => null,
            ];
        }
        if ($location->getAdm2()) {
            return [
                'adm1' => $location->getAdm2()->getAdm1()->getId(),
                'adm2' => $location->getAdm2()->getId(),
                'adm3' => null,
                'adm4' => null,
            ];
        }
        if ($location->getAdm1()) {
            return [
                'adm1' => $location->getAdm1()->getId(),
                'adm2' => null,
                'adm3' => null,
                'adm4' => null,
            ];
        }
        return [
            'adm1' => null,
            'adm2' => null,
            'adm3' => null,
            'adm4' => null,
        ];
    }
}
