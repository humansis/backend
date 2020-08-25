<?php

namespace ProjectBundle\Mapper;

use BeneficiaryBundle\Mapper\AssistanceMapper;
use ProjectBundle\Entity\Project;

class ProjectMapper
{
    /** @var DonorMapper */
    private $donorMapper;

    /** @var SectorMapper */
    private $sectorMapper;

    /** @var AssistanceMapper */
    private $assistanceMapper;

    /**
     * ProjectMapper constructor.
     *
     * @param DonorMapper      $donorMapper
     * @param SectorMapper     $sectorMapper
     * @param AssistanceMapper $assistanceMapper
     */
    public function __construct(DonorMapper $donorMapper, SectorMapper $sectorMapper, \BeneficiaryBundle\Mapper\AssistanceMapper $assistanceMapper)
    {
        $this->donorMapper = $donorMapper;
        $this->sectorMapper = $sectorMapper;
        $this->assistanceMapper = $assistanceMapper;
    }

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
            'donors' => $this->donorMapper->toMinimalArrays($project->getDonors()),
            'end_date' => $project->getEndDate()->format('Y-m-d'),
            'start_date' => $project->getStartDate()->format('Y-m-d'),
            'number_of_households' => $project->getNumberOfHouseholds(),
            'sectors' => $this->sectorMapper->toArrays($project->getSectors()),
            'distributions' => $this->assistanceMapper->toBeneficiaryOnlyArrays($project->getDistributions()),
        ];
    }

    public function toFullArrays(array $projects): iterable
    {
        foreach ($projects as $project) {
            yield $this->toFullArray($project);
        }
    }
}
