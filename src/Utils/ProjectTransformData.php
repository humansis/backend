<?php

declare(strict_types=1);

namespace Utils;

class ProjectTransformData
{
    /**
    * Returns an array representation of projects in order to prepare the export
    *
    * @param $projects
    *
    * @return array
    */
    public function transformData($projects): array
    {
        $exportableTable = [];

        foreach ($projects as $project) {
            $donors = [];
            foreach ($project->getDonors()->getValues() as $value) {
                $donors [] = $value->getFullname();
            }
            $donors = join(',', $donors);

            $sectors = [];
            foreach ($project->getSectors()->getValues() as $value) {
                $sectors [] = $value->getName();
            }
            $sectors = join(',', $sectors);

            $exportableTable [] = [
                "ID" => $project->getId(),
                "Project name" => $project->getName(),
                "Internal ID" => $project->getInternalId(),
                "Start date" => $project->getStartDate()->format('d-m-Y'),
                "End date" => $project->getEndDate()->format('d-m-Y'),
                "Number of households" => $project->getNumberOfHouseholds(),
                "Total Target beneficiaries" => $project->getTarget(),
                "Notes" => $project->getNotes(),
                "Country" => $project->getCountryIso3(),
                "Donors" => $donors,
                "Sectors" => $sectors,
                "is archived" => $project->getArchived(),
            ];
        }

        return $exportableTable;
    }
}
