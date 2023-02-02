<?php

declare(strict_types=1);

namespace Utils;

class DonorTransformData
{
    /**
     * Returns an array representation of donors in order to prepare the export
     *
     * @param $donors
     *
     * @return array
     */
    public function transformData($donors): array
    {
        $exportableTable = [];

        foreach ($donors as $donor) {
            $project = [];
            foreach ($donor->getProjects()->getValues() as $value) {
                $project[] = $value->getName();
            }
            $project = join(',', $project);

            $exportableTable [] =  [
                "Full name" => $donor->getFullName(),
                "Short name" => $donor->getShortname(),
                "Date added" => $donor->getDateAdded()->format('d-m-Y H:i:s'),
                "Notes" => $donor->getNotes(),
                "Project" => $project,
            ];
        }

        return $exportableTable;
    }
}
