<?php declare(strict_types=1);

namespace Component\Assistance;

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
use Repository\ModalityTypeRepository;
use Utils\CriteriaAssistanceService;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Component\Assistance\Domain;
use InputType\AssistanceCreateInputType;
use Repository\AssistanceStatisticsRepository;
use Repository\ScoringBlueprintRepository;
use Entity\Project;
use Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Cache\CacheInterface;

class AssistanceFactory
{
    /** @var CacheInterface */
    private $cache;

    /** @var CriteriaAssistanceService */
    private $criteriaAssistanceService;

    /** @var SerializerInterface */
    private $serializer;

    /** @var ModalityTypeRepository */
    private $modalityTypeRepository;

    /** @var LocationRepository */
    private $locationRepository;

    /** @var ProjectRepository */
    private $projectRepository;

    /** @var CommunityRepository */
    private $communityRepository;

    /** @var InstitutionRepository */
    private $institutionRepository;

    /** @var BeneficiaryRepository */
    private $beneficiaryRepository;

    /** @var AssistanceStatisticsRepository */
    private $assistanceStatisticRepository;

    /** @var Registry */
    private $workflowRegistry;

    /** @var AssistanceBeneficiaryRepository */
    private $targetRepository;

    /** @var SelectionCriteriaFactory */
    private $selectionCriteriaFactory;

    /** @var ScoringBlueprintRepository */
    private $scoringBlueprintRepository;

    /**
     * @param CacheInterface                  $cache
     * @param CriteriaAssistanceService       $criteriaAssistanceService
     * @param SerializerInterface             $serializer
     * @param ModalityTypeRepository          $modalityTypeRepository
     * @param LocationRepository              $locationRepository
     * @param ProjectRepository               $projectRepository
     * @param CommunityRepository             $communityRepository
     * @param InstitutionRepository           $institutionRepository
     * @param BeneficiaryRepository           $beneficiaryRepository
     * @param AssistanceStatisticsRepository  $assistanceStatisticRepository
     * @param Registry                        $workflowRegistry
     * @param AssistanceBeneficiaryRepository $targetRepository
     * @param SelectionCriteriaFactory        $selectionCriteriaFactory
     * @param ScoringBlueprintRepository      $scoringBlueprintRepository
     */
    public function __construct(
        CacheInterface                  $cache,
        CriteriaAssistanceService       $criteriaAssistanceService,
        SerializerInterface             $serializer,
        ModalityTypeRepository          $modalityTypeRepository,
        LocationRepository              $locationRepository,
        ProjectRepository               $projectRepository,
        CommunityRepository             $communityRepository,
        InstitutionRepository           $institutionRepository,
        BeneficiaryRepository           $beneficiaryRepository,
        AssistanceStatisticsRepository  $assistanceStatisticRepository,
        Registry                        $workflowRegistry,
        AssistanceBeneficiaryRepository $targetRepository,
        SelectionCriteriaFactory        $selectionCriteriaFactory,
        ScoringBlueprintRepository      $scoringBlueprintRepository
    ) {
        $this->cache = $cache;
        $this->criteriaAssistanceService = $criteriaAssistanceService;
        $this->serializer = $serializer;
        $this->modalityTypeRepository = $modalityTypeRepository;
        $this->locationRepository = $locationRepository;
        $this->projectRepository = $projectRepository;
        $this->communityRepository = $communityRepository;
        $this->institutionRepository = $institutionRepository;
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->assistanceStatisticRepository = $assistanceStatisticRepository;
        $this->workflowRegistry = $workflowRegistry;
        $this->targetRepository = $targetRepository;
        $this->selectionCriteriaFactory = $selectionCriteriaFactory;
        $this->scoringBlueprintRepository = $scoringBlueprintRepository;
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
        /** @var Project $project */
        $project = $this->projectRepository->find($inputType->getProjectId());
        if (!$project) {
            throw new EntityNotFoundException('Project #'.$inputType->getProjectId().' does not exists.');
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
            $scoringBlueprint = $this->scoringBlueprintRepository->findActive($inputType->getScoringBlueprintId(), $location->getCountryIso3());
            if (!$scoringBlueprint) {
                throw new EntityNotFoundException('Scoring blueprint #'.$inputType->getScoringBlueprintId().' does not exists.');
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

        return $assistance;
    }

    private function checkExpirationDate(AssistanceCreateInputType $inputType, Project $project)
    {
        $dateToCheck = $inputType->getDateExpiration() === null ? $inputType->getDateDistribution() : $inputType->getDateExpiration();

        if ($dateToCheck > $project->getEndDate()) {
            throw new BadRequestHttpException('Expiration / Distribution date of assistance must be earlier than the end of project');
        }
    }

    public function hydrate(Entity\Assistance $assistance): Domain\Assistance
    {
        return new Domain\Assistance(
            $assistance,
            $this->cache,
            $this->modalityTypeRepository,
            $this->assistanceStatisticRepository,
            $this->workflowRegistry,
            $this->targetRepository,
            $this->selectionCriteriaFactory
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