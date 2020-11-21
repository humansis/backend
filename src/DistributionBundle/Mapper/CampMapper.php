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

        if ($camp->getLocation()->getAdm1()) {
            $adm1 = [
                'id' => $camp->getLocation()->getAdm1()->getId(),
                'name' => $camp->getLocation()->getAdm1()->getName(),
                'country_i_s_o3' => $camp->getLocation()->getAdm1()->getCountryISO3(),
                'code' => $camp->getLocation()->getAdm1()->getCode(),
                'location' => [
                    'id' => $camp->getLocation()->getAdm1()->getLocation()->getId(),
                ],
            ];
        }
        if ($camp->getLocation()->getAdm2()) {
            $adm2 = [
                'id' => $camp->getLocation()->getAdm2()->getId(),
                'name' => $camp->getLocation()->getAdm2()->getName(),
                'code' => $camp->getLocation()->getAdm2()->getCode(),
                'location' => [
                    'id' => $camp->getLocation()->getAdm2()->getLocation()->getId(),
                ],
                'adm1' => $this->getAdm1($camp->getLocation()->getAdm2()->getAdm1()),
            ];
        }
        if ($camp->getLocation()->getAdm3()) {
            $adm3 = [
                'id' => $camp->getLocation()->getAdm3()->getId(),
                'name' => $camp->getLocation()->getAdm3()->getName(),
                'code' => $camp->getLocation()->getAdm3()->getCode(),
                'location' => [
                    'id' => $camp->getLocation()->getAdm3()->getLocation()->getId(),
                ],
                'adm2' => $this->getAdm2($camp->getLocation()->getAdm3()->getAdm2()),
            ];
        }
        if ($camp->getLocation()->getAdm4()) {
            $adm4 = [
                'id' => $camp->getLocation()->getAdm4()->getId(),
                'name' => $camp->getLocation()->getAdm4()->getName(),
                'code' => $camp->getLocation()->getAdm4()->getCode(),
                'location' => [
                    'id' => $camp->getLocation()->getAdm4()->getLocation()->getId(),
                ],
                'adm3' => $this->getAdm3($camp->getLocation()->getAdm4()->getAdm3()),
            ];
        }

        $location = ['id' => $camp->getLocation()->getId(),];

        if (isset($adm4)) {
            $location['adm4'] = $adm4;
        } elseif (isset($adm3)) {
            $location['adm3'] = $adm3;
        } elseif (isset($adm2)) {
            $location['adm2'] = $adm2;
        } elseif (isset($adm1)) {
            $location['adm1'] = $adm1;
        }

        return $location;
    }

    private function getAdm3(\CommonBundle\Entity\Adm3 $adm3)
    {
        return [
            'id' => $adm3->getId(),
            'name' => $adm3->getName(),
            'code' => $adm3->getCode(),
            'location' => [
                'id' => $adm3->getLocation()->getId(),
            ],
            'adm2' => $this->getAdm2($adm3->getAdm2()),
        ];
    }

    private function getAdm2(\CommonBundle\Entity\Adm2 $adm2)
    {
        return [
            'id' => $adm2->getId(),
            'name' => $adm2->getName(),
            'code' => $adm2->getCode(),
            'location' => [
                'id' => $adm2->getLocation()->getId(),
            ],
            'adm1' => $this->getAdm1($adm2->getAdm1()),
        ];
    }

    private function getAdm1(\CommonBundle\Entity\Adm1 $adm1)
    {
        return [
            'id' => $adm1->getId(),
            'name' => $adm1->getName(),
            'country_i_s_o3' => $adm1->getCountryISO3(),
            'code' => $adm1->getCode(),
            'location' => [
                'id' => $adm1->getId(),
            ],
        ];
    }
}
