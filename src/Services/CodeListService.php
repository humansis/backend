<?php

namespace Services;

use Component\Codelist\CodeItem;
use DTO\Sector;
use Symfony\Contracts\Translation\TranslatorInterface;

class CodeListService
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function mapEnum(iterable $list): array
    {
        $data = [];
        foreach ($list as $value) {
            $data[] = new CodeItem($value, $this->translator->trans($value));
        }

        return $data;
    }

    public function mapArray(iterable $list): array
    {
        $data = [];
        foreach ($list as $key => $value) {
            $data[] = new CodeItem($key, $this->translator->trans($value));
        }

        return $data;
    }

    public function mapSubSectors(iterable $subSectors, ?string $domain = null): array
    {
        $data = [];

        /** @var Sector $subSector */
        foreach ($subSectors as $subSector) {
            $data[] = new CodeItem(
                $subSector->getSubSectorName(),
                $this->translator->trans('label_sector_' . $subSector->getSubSectorName())
            );
        }

        return $data;
    }
}
