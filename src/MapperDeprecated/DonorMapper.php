<?php

namespace MapperDeprecated;

use Entity\Donor;

/**
 * @deprecated
 */
class DonorMapper
{
    public function toMinimalArray(?Donor $donor): ?array
    {
        if (!$donor) {
            return null;
        }

        return [
            'id' => $donor->getId(),
            'fullname' => $donor->getFullname(),
            'shortname' => $donor->getShortname(),
        ];
    }

    public function toMinimalArrays(iterable $projects): iterable
    {
        foreach ($projects as $project) {
            yield $this->toMinimalArray($project);
        }
    }
}
