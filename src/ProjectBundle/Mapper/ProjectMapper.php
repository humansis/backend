<?php declare(strict_types=1);

namespace ProjectBundle\Mapper;

use BeneficiaryBundle\Mapper\AssistanceMapper;
use BeneficiaryBundle\Repository\BeneficiaryRepository;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\Assistance;
use ProjectBundle\Entity\Project;
use ProjectBundle\Utils\SectorService;

class ProjectMapper
{
    /** @var DonorMapper */
    private $donorMapper;

    /** @var SectorMapper */
    private $sectorMapper;

    /** @var SectorService */
    private $sectorService;

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
     * @param SectorService         $sectorService
     */
    public function __construct(
        DonorMapper $donorMapper,
        SectorMapper $sectorMapper,
        AssistanceMapper $assistanceMapper,
        BeneficiaryRepository $beneficiaryRepo,
        SectorService $sectorService
    ) {
        $this->donorMapper = $donorMapper;
        $this->sectorMapper = $sectorMapper;
        $this->assistanceMapper = $assistanceMapper;
        $this->beneficiaryRepo = $beneficiaryRepo;
        $this->sectorService = $sectorService;
    }

    public function toFullArray(?Project $project): ?array
    {
        if (!$project) {
            return null;
        }
        $bnfCount = $this->beneficiaryRepo->countAllInProject($project);
        return [
            'id' => $project->getId(),
            'iso3' => $project->getCountryIso3(),
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

    public function toIdArray(iterable $projects): iterable
    {
        foreach ($projects as $project) {
            yield $project->getId();
        }
    }
}
