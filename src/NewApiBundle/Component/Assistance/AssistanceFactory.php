<?php declare(strict_types=1);

namespace NewApiBundle\Component\Assistance;

use BeneficiaryBundle\Repository\BeneficiaryRepository;
use BeneficiaryBundle\Repository\CommunityRepository;
use BeneficiaryBundle\Repository\InstitutionRepository;
use CommonBundle\Entity\Location;
use CommonBundle\Repository\LocationRepository;
use DateTimeInterface;
use DistributionBundle\Entity;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Repository\AssistanceBeneficiaryRepository;
use DistributionBundle\Repository\ModalityTypeRepository;
use DistributionBundle\Utils\CriteriaAssistanceService;
use Doctrine\ORM\EntityNotFoundException;
use NewApiBundle\Component\Assistance\Domain;
use NewApiBundle\Component\Assistance\DTO\CriteriaGroup;
use NewApiBundle\Component\SelectionCriteria\FieldDbTransformer;
use NewApiBundle\Entity\Assistance\SelectionCriteria;
use NewApiBundle\InputType\Assistance\SelectionCriterionInputType;
use NewApiBundle\InputType\AssistanceCreateInputType;
use NewApiBundle\Repository\AssistanceStatisticsRepository;
use ProjectBundle\Repository\ProjectRepository;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Cache\CacheInterface;

class AssistanceFactory
{
    /** @var CacheInterface */
    private $cache;

    /** @var CriteriaAssistanceService */
    private $criteriaAssistanceService;

    /** @var FieldDbTransformer */
    private $fieldDbTransformer;

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

    /**
     * @param CacheInterface                  $cache
     * @param CriteriaAssistanceService       $criteriaAssistanceService
     * @param FieldDbTransformer              $fieldDbTransformer
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
     */
    public function __construct(
        CacheInterface                  $cache,
        CriteriaAssistanceService       $criteriaAssistanceService,
        FieldDbTransformer              $fieldDbTransformer,
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
        SelectionCriteriaFactory        $selectionCriteriaFactory
    ) {
        $this->cache = $cache;
        $this->criteriaAssistanceService = $criteriaAssistanceService;
        $this->fieldDbTransformer = $fieldDbTransformer;
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
    }

    public function create(AssistanceCreateInputType $inputType): Domain\Assistance
    {
        $assistanceRoot = new Entity\Assistance();
        $assistanceRoot->setAssistanceType($inputType->getType());
        $assistanceRoot->setTargetType($inputType->getTarget());
        $assistanceRoot->setDateDistribution($inputType->getDateDistribution());
        $assistanceRoot->setDateExpiration($inputType->getDateExpiration());
        $assistanceRoot->setSector($inputType->getSector());
        $assistanceRoot->setSubSector($inputType->getSubsector());
        $assistanceRoot->setHouseholdsTargeted($inputType->getHouseholdsTargeted());
        $assistanceRoot->setIndividualsTargeted($inputType->getIndividualsTargeted());
        $assistanceRoot->setDescription($inputType->getDescription());
        $assistanceRoot->setFoodLimit($inputType->getFoodLimit());
        $assistanceRoot->setNonFoodLimit($inputType->getNonFoodLimit());
        $assistanceRoot->setCashbackLimit($inputType->getCashbackLimit());
        $assistanceRoot->setRemoteDistributionAllowed($inputType->getRemoteDistributionAllowed());
        $assistanceRoot->setAllowedProductCategoryTypes($inputType->getAllowedProductCategoryTypes());

        $location = $this->locationRepository->find($inputType->getLocationId());
        $assistanceRoot->setLocation($location);
        $assistanceRoot->setName(self::generateName($location, $inputType->getDateDistribution()));

        $project = $this->projectRepository->find($inputType->getProjectId());
        if (!$project) {
            throw new EntityNotFoundException('Project #'.$inputType->getProjectId().' does not exists.');
        }
        $assistanceRoot->setProject($project);

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
                    false
                );
                foreach ($beneficiaryIds['finalArray'] as $beneficiaryId => $vulnerabilityScore) {
                    $individualOrHHH = $this->beneficiaryRepository->find($beneficiaryId);
                    $assistance->addBeneficiary($individualOrHHH, null, $vulnerabilityScore);
                }
                break;
        }

        return $assistance;
    }

    private static function generateName(Location $location, ?DateTimeInterface $date = null): string
    {
        $adm = '';
        if ($location->getAdm4()) {
            $adm = $location->getAdm4()->getName();
        } elseif ($location->getAdm3()) {
            $adm = $location->getAdm3()->getName();
        } elseif ($location->getAdm2()) {
            $adm = $location->getAdm2()->getName();
        } elseif ($location->getAdm1()) {
            $adm = $location->getAdm1()->getName();
        }

        if ($date) {
            return $adm.'-'.$date->format('d-m-Y');
        } else {
            return $adm.'-'.date('d-m-Y');
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
            $this->fieldDbTransformer,
            $this->selectionCriteriaFactory
        );
    }
}
