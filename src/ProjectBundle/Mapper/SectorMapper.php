<?php

namespace ProjectBundle\Mapper;

use ProjectBundle\Entity\Donor;
use ProjectBundle\Entity\Project;
use ProjectBundle\Entity\Sector;

class SectorMapper
{
    public function toArray(?Sector $sector): ?array
    {
        if (!$sector) {
            return null;
        }
        return [
            'id' => $sector->getId(),
            'name' => $sector->getName(),
        ];
    }

    public function toArrays(iterable $projects): iterable
    {
        foreach ($projects as $project) {
            yield $this->toArray($project);
        }
    }
}
