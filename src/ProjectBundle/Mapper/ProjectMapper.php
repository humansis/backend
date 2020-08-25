<?php

namespace ProjectBundle\Mapper;

use ProjectBundle\Entity\Project;

class ProjectMapper
{
    public function toFullArray(?Project $project): ?array
    {
        if (!$project) {
            return null;
        }
        return [
            'id' => $project->getId(),
            'iso3' => $project->getIso3(),
            'name' => $project->getName(),
            'notes' => $project->getNotes(),
            'target' => $project->getTarget(),
            'donors' => $project->getDonors(),
            'end_date' => $project->getEndDate()->format('YYYY-MM-DD'),
            'start_date' => $project->getStartDate()->format('YYYY-MM-DD'),
            'number_of_households' => $project->getNumberOfHouseholds(),
            'sectors' => $project->getSectors(),
        ];
    }

    public function toFullArrays(array $projects): iterable
    {
        foreach ($projects as $project) {
            yield $this->toFullArray($project);
        }
    }
}
