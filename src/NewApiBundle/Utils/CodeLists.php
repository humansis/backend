<?php


namespace NewApiBundle\Utils;


use ProjectBundle\DTO\Sector;

class CodeLists
{
    public static function mapEnum(iterable $list)
    {
        $data = [];
        foreach ($list as $value) {
            $data[] = ['code' => $value, 'value' => $value];
        }

        return $data;
    }

    public static function mapArray(iterable $list)
    {
        $data = [];
        foreach ($list as $key => $value) {
            $data[] = ['code' => (string) $key, 'value' => $value];
        }

        return $data;
    }

    public static function mapSubSectors(iterable $subSectors)
    {
        $data = [];

        /** @var Sector $subSector */
        foreach ($subSectors as $subSector) {
            $data[] = ['code' => $subSector->getSubSectorName(), 'value' => $subSector->getSubSectorName()];
        }

        return $data;
    }
}