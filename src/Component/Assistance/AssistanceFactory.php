<?php

declare(strict_types=1);

namespace Component\Assistance;

use Component\Assistance\Services\AssistanceBeneficiaryService;
use Component\Auditor\AuditorService;
use Component\ReliefPackage\ReliefPackageService;
use Doctrine\ORM\Exception\ORMException;
use Entity\AbstractBeneficiary;
use Entity\Assistance;
use Exception\CsvParserException;
use Repository\BeneficiaryRepository;
use Repository\CommunityRepository;
use Repository\InstitutionRepository;
use Repository\LocationRepository;
use Entity;
use Enum\AssistanceTargetType;
use Repository\AssistanceBeneficiaryRepository;
use Symfony\Contracts\Translation\TranslatorInterface;
use Utils\CriteriaAssistanceService;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Component\Assistance\Domain;
use InputType\AssistanceCreateInputType;
use Repository\AssistanceStatisticsRepository;
use Repository\ScoringBlueprintRepository;
use Entity\Project;
use Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Utils\ProjectService;

class AssistanceFactory
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly CriteriaAssistanceService $criteriaAssistanceService,
        private readonly LocationRepository $locationRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly CommunityRepository $communityRepository,
        private readonly InstitutionRepository $institutionRepository,
        private readonly BeneficiaryRepository $beneficiaryRepository,
        private readonly AssistanceStatisticsRepository $assistanceStatisticRepository,
        private readonly AssistanceBeneficiaryRepository $targetRepository,
        private readonly SelectionCriteriaFactory $selectionCriteriaFactory,
        private readonly ScoringBlueprintRepository $scoringBlueprintRepository,
        private readonly AuditorService $auditorService,
        private readonly ReliefPackageService $reliefPackageService,
        private readonly AssistanceBeneficiaryService $assistanceBeneficiaryService,
        private readonly TranslatorInterface $translator,
        private readonly ProjectService $projectService,
    ) {
    }

    /**
     * @throws CsvParserException
     * @throws ORMException
     * @throws NonUniqueResultException
     * @throws EntityNotFoundException
     * @throws NoResultException
     */
    public function create(AssistanceCreateInputType $inputType): Domain\Assistance
    {
        $this->auditorService->disableAuditing();

        /** @var Project $project */
        $project = $this->projectRepository->find($inputType->getProjectId());
        if (!$project) {
            throw new EntityNotFoundException('Project #' . $inputType->getProjectId() . ' does not exists.');
        }

        $this->checkExpirationDate($inputType, $project);

        $assistanceRoot = new Entity\Assistance();
        $assistanceRoot->setProject($project);
        $assistanceRoot->setAssistanceType($inputType->getType());
        $assistanceRoot->setTargetType($inputType->getTarget());
        $assistanceRoot->setDateDistribution($inputType->getDateDistribution());
        $assistanceRoot->setDateExpiration($inputType->getDateExpiration());
        $assistanceRoot->setSector($inputType->getSector());
        $assistanceRoot->setSubSector($inputType->getSubsector());
        $assistanceRoot->setRound($inputType->getRound());

        $assistanceRoot->setHouseholdsTargeted($inputType->getHouseholdsTargeted());
        $assistanceRoot->setIndividualsTargeted($inputType->getIndividualsTargeted());
        $assistanceRoot->setDescription($inputType->getDescription());
        $assistanceRoot->setFoodLimit($inputType->getFoodLimit());
        $assistanceRoot->setNonFoodLimit($inputType->getNonFoodLimit());
        $assistanceRoot->setCashbackLimit($inputType->getCashbackLimit());
        $assistanceRoot->setRemoteDistributionAllowed($inputType->getRemoteDistributionAllowed());
        $assistanceRoot->setAllowedProductCategoryTypes($inputType->getAllowedProductCategoryTypes());
        $assistanceRoot->setNote($inputType->getNote());

        $location = $this->locationRepository->find($inputType->getLocationId());
        $assistanceRoot->setLocation($location);
        $assistanceRoot->setName(self::generateName($assistanceRoot));

        if (!is_null($inputType->getScoringBlueprintId())) {
            $scoringBlueprint = $this->scoringBlueprintRepository->findActive(
                $inputType->getScoringBlueprintId(),
                $location->getCountryIso3()
            );
            if (!$scoringBlueprint) {
                throw new EntityNotFoundException(
                    'Scoring blueprint #' . $inputType->getScoringBlueprintId() . ' does not exists.'
                );
            }
            $assistanceRoot->setScoringBlueprint($scoringBlueprint);
        }

        $assistance = $this->hydrate($assistanceRoot);

        foreach ($inputType->getCommodities() as $commodityInputType) {
            $assistance->addCommodity($commodityInputType);
        }

        switch ($inputType->getTarget()) {
            case AssistanceTargetType::COMMUNITY:
                foreach ($inputType->getCommunities() as $communityId) {
                    $community = $this->communityRepository->find($communityId);
                    $assistance->addBeneficiary($community);
                }
                break;
            case AssistanceTargetType::INSTITUTION:
                foreach ($inputType->getInstitutions() as $institutionId) {
                    /** @var AbstractBeneficiary $individualOrHHH */
                    $individualOrHHH = $this->institutionRepository->find($institutionId);
                    $assistance->addBeneficiary($individualOrHHH);
                }
                break;
            case AssistanceTargetType::INDIVIDUAL:
            case AssistanceTargetType::HOUSEHOLD:
            default:
                $groups = $this->selectionCriteriaFactory->createGroups($inputType->getSelectionCriteria());
                foreach ($groups as $criteriumGroup) {
                    foreach ($criteriumGroup->getCriteria() as $criterion) {
                        $assistance->addSelectionCriteria($criterion);
                    }
                }
                $assistanceRoot->getAssistanceSelection()->setThreshold($inputType->getThreshold());
                // WARNING: those are mixed Individual BNF IDs or HHH IDs of HH (HH ID aren't currently used)
                $beneficiaryIds = $this->criteriaAssistanceService->load(
                    $assistance->getSelectionCriteriaGroups(),
                    $project,
                    $assistanceRoot->getTargetType(),
                    $assistanceRoot->getSector(),
                    $assistanceRoot->getSubSector(),
                    $inputType->getThreshold(),
                    false,
                    $inputType->getScoringBlueprintId()
                );
                foreach ($beneficiaryIds['finalArray'] as $beneficiaryId => $vulnerabilityScore) {
                    $individualOrHHH = $this->beneficiaryRepository->find($beneficiaryId);
                    /** @var AbstractBeneficiary $individualOrHHH */
                    $assistance->addBeneficiary($individualOrHHH, null, $vulnerabilityScore);
                }
                break;
        }

        $this->projectService->removeAssistanceCountCache($project);

        return $assistance;
    }

    private function checkExpirationDate(AssistanceCreateInputType $inputType, Project $project): void
    {
        $dateToCheck = $inputType->getDateExpiration() ?? $inputType->getDateDistribution(
        );

        if ($dateToCheck > $project->getEndDate()) {
            throw new BadRequestHttpException(
                'Expiration / Distribution date of assistance must be earlier than the end of project'
            );
        }
    }

    public function hydrate(Entity\Assistance $assistance): Domain\Assistance
    {
        return new Domain\Assistance(
            $assistance,
            $this->cache,
            $this->assistanceStatisticRepository,
            $this->targetRepository,
            $this->selectionCriteriaFactory,
            $this->reliefPackageService,
            $this->assistanceBeneficiaryService,
            $this->translator,
        );
    }

    public static function generateName(Assistance $assistance): string
    {
        $adm = $assistance->getLocation()->getName();

        $round = $assistance->getRound();

        if ($round !== null) {
            $adm .= " #$round";
        }

        return $adm . " • " . $assistance->getDateDistribution()->format('Y-m-d');
    }
}
