<?php

namespace Services;

use Component\Codelist\CodeItem;
use DTO\Sector;
use Entity\VulnerabilityCriterion;
use Enum\Domain;
use Symfony\Contracts\Translation\TranslatorInterface;

class CodeListService
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var string */
    private $defaultDomain = Domain::MESSAGES;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function mapEnum(iterable $list, ?string $domain = null): array
    {
        $data = [];
        foreach ($list as $value) {
            $data[] = new CodeItem($value, $this->translator->trans($value, [], $domain ?? $this->defaultDomain));
        }

        return $data;
    }

    public function mapArray(iterable $list, ?string $domain = null): array
    {
        $data = [];
        foreach ($list as $key => $value) {
            $data[] = new CodeItem($key, $this->translator->trans($value, [], $domain ?? $this->defaultDomain));
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
                $this->translator->trans('label_sector_' . $subSector->getSubSectorName(), [], $domain ?? $this->defaultDomain)
                //SubSectorEnum::translate($subSector->getSubSectorName())
            );
        }

        return $data;
    }

    public function mapCriterion(iterable $criteria): array
    {
        $data = [];

        /* @var VulnerabilityCriterion $criterion */
        foreach ($criteria as $criterion) {
            if ($criterion->isActive()) {
                $data[] = new CodeItem(
                    $criterion->getFieldString(),
                    $this->translator->trans(VulnerabilityCriterion::all()[$criterion->getFieldString()])
                );
            }
        }

        return $data;
    }
}