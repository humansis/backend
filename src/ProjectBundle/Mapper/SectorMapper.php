<?php
namespace ProjectBundle\Mapper;

use ProjectBundle\DTO\Sector;
use ProjectBundle\Entity\ProjectSector;
use Symfony\Component\Translation\TranslatorInterface;

class SectorMapper
{
    /** @var TranslatorInterface */
    private $translator;

    /**
     * SectorMapper constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    private function toSubArray(string $sector, iterable $subSectors): array
    {
        $subSectorMapped = [];

        /** @var Sector $subSector */
        foreach ($subSectors as $subSector) {
            $ss = [
                'id' => $subSector->getSubSectorName(),
                'name' => $this->getLabel($subSector->getSubSectorName()),
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
            'name' => $this->getLabel($sector),
            'subSectors' => $subSectorMapped,
        ];
    }

    private function getLabel(string $enumValue): string
    {
        return $this->translator->trans('label_sector_'.$enumValue, [], 'sectors', 'en');
    }

    public function listToSubArrays(iterable $sectorTree): iterable
    {
        foreach ($sectorTree as $sector => $subSectors) {
            yield $this->toSubArray($sector, $subSectors);
        }
    }

    /**
     * @param ProjectSector[] $projectSectors
     *
     * @return string[]
     */
    public function toSectorArray(iterable $projectSectors): iterable
    {
        foreach ($projectSectors as $projectSector) {
            yield [
                "id" => $projectSector->getSector(),
                "name" => $this->getLabel($projectSector->getSector()),
            ];
        }
    }
}
