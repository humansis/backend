<?php

declare(strict_types=1);

namespace Component\Codelist;

use Entity\VulnerabilityCriterion;
use DBAL\SubSectorEnum;
use DTO\Sector;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @deprecated use CodeListService instead */
class CodeLists
{
    public static function mapEnum(
        iterable $list,
        ?TranslatorInterface $translator = null
    )
    {
        $data = [];
        foreach ($list as $value) {
            $translation = $translator !== null
                ? $translator->trans($value)
                : $value;

            $data[] = new CodeItem($value, $translation);
        }

        return $data;
    }

    public static function mapArray(
        iterable $list,
        ?TranslatorInterface $translator = null
    )
    {
        $data = [];
        foreach ($list as $key => $value) {
            $translation = $translator !== null
                ? $translator->trans($value)
                : $value;

            $data[] = new CodeItem($key, $translation);
        }

        return $data;
    }

    public static function mapSubSectors(iterable $subSectors)
    {
        $data = [];

        /** @var Sector $subSector */
        foreach ($subSectors as $subSector) {
            $data[] = new CodeItem($subSector->getSubSectorName(), SubSectorEnum::translate($subSector->getSubSectorName()));
        }

        return $data;
    }

    public static function mapCriterion(iterable $criteria, ?TranslatorInterface $translator = null)
    {
        $data = [];

        /* @var VulnerabilityCriterion $criterion */
        foreach ($criteria as $criterion) {
            if ($criterion->isActive()) {
                $translation = $translator !== null
                    ? $translator->trans(VulnerabilityCriterion::all()[$criterion->getFieldString()])
                    : VulnerabilityCriterion::all()[$criterion->getFieldString()];

                $data[] = new CodeItem($criterion->getFieldString(), $translation);
            }
        }

        return $data;
    }
}
