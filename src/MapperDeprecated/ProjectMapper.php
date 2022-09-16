<?php declare(strict_types=1);

namespace MapperDeprecated;

use Repository\BeneficiaryRepository;
use Entity\Project;

/**
 * @deprecated
 */
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
            'internal_id' => $project->getInternalId(),
            'donors' => $this->donorMapper->toMinimalArrays($project->getDonors()),
            'end_date' => $project->getEndDate()->format('d-m-Y'),
            'start_date' => $project->getStartDate()->format('d-m-Y'),
            'number_of_households' => $project->getNumberOfHouseholds(),
            'sectors' => $this->sectorMapper->toSectorArray($project->getSectors()),
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
