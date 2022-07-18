<?php

declare(strict_types=1);

namespace DistributionBundle\Mapper;

use BeneficiaryBundle\Entity\Camp;

class CampMapper
{
    public function toArray(?Camp $camp): ?array
    {
        if (!$camp) {
            return null;
        }

        return [
            'id' => $camp->getId(),
            'name' => $camp->getName(),
            'location' => $this->getLocation($camp),
        ];
    }

    public function toArrays(iterable $camps): iterable
    {
        foreach ($camps as $assistanceBeneficiary) {
            yield $this->toArray($assistanceBeneficiary);
        }
    }

    private function getLocation(Camp $camp)
    {
        if (!$camp->getLocation()) {
            return null;
        }

        $location = $camp->getLocation();
        $locationArray = ['id' => $location->getId()];
        
        while ($location !== null) {
            $locationArray['adm' . $location->getLvl()] = [
                'id' => $location->getId(),
                'name' => $location->getName(),
                'country_i_s_o3' => $location->getCountryIso3(),
                'code' => $location->getCode(),
                'location' => [
                    'id' => $location->getId(),
                ],
            ];
            
            $location = $location->getParent();
        }

        return $locationArray;
    }
}
