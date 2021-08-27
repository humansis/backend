<?php

declare(strict_types=1);

namespace NewApiBundle\Component\Codelist;

use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Enum\Vulnerabilities;
use ProjectBundle\DBAL\SubSectorEnum;
use ProjectBundle\DTO\Sector;

class CodeLists
{
    public static function mapEnum(iterable $list)
    {
        $data = [];
        foreach ($list as $value) {
            $data[] = new CodeItem($value, $value);
        }

        return $data;
    }

    public static function mapArray(iterable $list)
    {
        $data = [];
        foreach ($list as $key => $value) {
            $data[] = new CodeItem($key, $value);
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

    public static function mapCriterion(iterable $criteria)
    {
        $data = [];

        /* @var VulnerabilityCriterion $criterion */
        foreach ($criteria as $criterion) {
            if ($criterion->isActive()) {
                $data[] = new CodeItem($criterion->getFieldString(), Vulnerabilities::getByKey($criterion->getFieldString()));
            }
        }

        return $data;
    }
}
