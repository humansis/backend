<?php

namespace ProjectBundle\Mapper;

use BeneficiaryBundle\Mapper\AssistanceMapper;
use BeneficiaryBundle\Repository\BeneficiaryRepository;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Entity\DistributionData;
use ProjectBundle\Entity\Project;

class ProjectMapper
{
    /** @var DonorMapper */
    private $donorMapper;

    /** @var SectorMapper */
    private $sectorMapper;

    /** @var AssistanceMapper */
    private $assistanceMapper;

    /** @var BeneficiaryRepository */
    private $beneficiaryRepo;

    /**
     * ProjectMapper constructor.
     *
     * @param DonorMapper           $donorMapper
     * @param SectorMapper          $sectorMapper
     * @param AssistanceMapper      $assistanceMapper
     * @param BeneficiaryRepository $beneficiaryRepo
     */
    public function __construct(
        DonorMapper $donorMapper,
        SectorMapper $sectorMapper,
        AssistanceMapper $assistanceMapper,
        BeneficiaryRepository $beneficiaryRepo
    ) {
        $this->donorMapper = $donorMapper;
        $this->sectorMapper = $sectorMapper;
        $this->assistanceMapper = $assistanceMapper;
        $this->beneficiaryRepo = $beneficiaryRepo;
    }

    public function toFullArray(?Project $project): ?array
    {
        if (!$project) {
            return null;
        }
        $bnfCount = $this->beneficiaryRepo->countAllInProject($project);
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
            'reached_beneficiaries' => $bnfCount,
            'distributions' => $this->assistanceMapper->toMinimalArrays($project->getDistributions()),
        ];
    }

    public function toFullArrays(array $projects): iterable
    {
        foreach ($projects as $project) {
            yield $this->toFullArray($project);
        }
    }
}
