<?php

declare(strict_types=1);

namespace ProjectBundle\Mapper;

use NewApiBundle\Entity\ProjectSector;
use NewApiBundle\Enum\Domain;
use ProjectBundle\DTO\Sector;
use ProjectBundle\Entity\ProjectSector;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @deprecated
 */
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

    private function getLabel(string $enumValue): string
    {
        return $this->translator->trans('label_sector_'.$enumValue, [], Domain::SECTORS, 'en');
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
                'id' => $projectSector->getSector(),
                'name' => $this->getLabel($projectSector->getSector()),
            ];
        }
    }
}
