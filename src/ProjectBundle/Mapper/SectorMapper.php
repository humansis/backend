<?php
namespace ProjectBundle\Mapper;

use ProjectBundle\Entity\Sector;

class SectorMapper
{
    private function toSubArray(string $sector, iterable $subSectors): array
    {
        $subSectorMapped = [];

        /** @var Sector $subSector */
        foreach ($subSectors as $subSector) {
            $ss = [
                'id' => $subSector->getSubSectorName(),
                'name' => $this->getLabelKeyFromEnum($subSector->getSubSectorName()),
                'availableTargets' => [],
                'assistanceType' => '',
            ];
            if ($subSector->isCommunityAllowed()) {
                $ss['availableTargets'][] = 'community';
            }
            if ($subSector->isInstitutionAllowed()) {
                $ss['availableTargets'][] = 'institution';
            }
            if ($subSector->isHouseholdAllowed()) {
                $ss['availableTargets'][] = 'household';
            }
            if ($subSector->isBeneficiaryAllowed()) {
                $ss['availableTargets'][] = 'individual';
            }
            if ($subSector->isActivityAllowed()) {
                $ss['assistanceType'] = 'activity';
            } elseif ($subSector->isDistributionAllowed()) {
                $ss['assistanceType'] = 'distribution';
            }
            $subSectorMapped[] = $ss;
        }
        return [
            'id' => $sector,
            'name' => $this->getLabelKeyFromEnum($sector),
            'subSectors' => $subSectorMapped,
        ];
    }

    private function getLabelKeyFromEnum(string $enumValue): string
    {
        return 'label_sector_'.$enumValue;
    }

    public function listToSubArrays(iterable $sectorTree): iterable
    {
        foreach ($sectorTree as $sector => $subSectors) {
            yield $this->toSubArray($sector, $subSectors);
        }
    }
}
