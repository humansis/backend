<?php

declare(strict_types=1);

namespace MapperDeprecated;

use Entity\ProjectSector;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @deprecated
 */
class SectorMapper
{
    /**
     * SectorMapper constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    private function getLabel(string $enumValue): string
    {
        return $this->translator->trans('label_sector_' . $enumValue, [], 'messages', 'en');
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
