<?php

namespace DataFixtures;

use Entity\Community;
use Entity\Institution;
use DataFixtures\Beneficiaries\BeneficiaryFixtures;
use Entity\Location;
use MapperDeprecated\LocationMapper;
use Entity\Modality;
use Enum\AssistanceTargetType;
use Enum\AssistanceType;
use Entity\ModalityType;
use Utils\AssistanceService;
use Entity\Community;
use Entity\Institution;
use Exception\CsvParserException;
use Repository\CommunityRepository;
use Repository\InstitutionRepository;
use DataFixtures\Beneficiaries\BeneficiaryFixtures;
use Entity\Location;
use Repository\LocationRepository;
use DateTime;
use DateTimeImmutable;
use Entity\Modality;
use Enum\AssistanceTargetType;
use Enum\AssistanceType;
use Entity\ModalityType;
use Repository\AssistanceRepository;
use Repository\ModalityRepository;
use Repository\ModalityTypeRepository;
use Utils\AssistanceService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectManager;
use LogicException;
use Component\Assistance\AssistanceFactory;
use Component\Assistance\Domain\Assistance;
use Component\Assistance\Enum\CommodityDivision;
use InputType\Assistance\CommodityInputType;
use InputType\Assistance\DivisionInputType;
use InputType\Assistance\SelectionCriterionInputType;
use InputType\AssistanceCreateInputType;
use Utils\ValueGenerator\ValueGenerator;
use Repository\ProjectRepository;
use Component\Country\Countries;
use Component\Country\Country;
use Enum\ProductCategoryType;
use DBAL\SectorEnum;
use DBAL\SubSectorEnum;
use Entity\Project;
use Symfony\Component\HttpKernel\Kernel;
use Entity\User;

class AssistanceFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    const REF_SMARTCARD_ASSISTANCE_KHM_KHR = '569f131a-387d-4588-9e17-ecd94f261a85';
    const REF_SMARTCARD_ASSISTANCE_KHM_USD = '9ab17087-f54f-41ee-9b8d-c91d932d8ec2';
    const REF_SMARTCARD_ASSISTANCE_SYR_SYP = 'e643bdbc-df6f-449a-b424-8c842a408e47';
    const REF_SMARTCARD_ASSISTANCE_SYR_USD = '223b91e8-0f05-44b4-9c74-f156cbd95d1a';

    // private $assistanceArray = [
    //     'adm1' => '',
    //     'adm2' => '',
    //     'adm3' => '',
    //     'adm4' => '',
    //     'commodities' => [
    //         0 => [
    //             'modality' => 'CTP',
    //             'modality_type' => [
    //                 'id' => 1,
    //             ],
    //             'type' => 'Mobile',
    //             'unit' => 'USD',
    //             'value' => 45,
    //             'description' => null,
    //         ],
    //     ],
    //     'date_distribution' => '13-09-2020',
    //     'location' => [
    //         'adm1' => 1,
    //         'adm2' => 1,
    //         'adm3' => null,
    //         'adm4' => null,
    //         'country_iso3' => 'KHM',
    //     ],
    //     'location_name' => '',
    //     'project' => [
    //         'donors' => [],
    //         'donors_name' => [],
    //         'id' => '?',
    //         'name' => 'Test assistance project',
    //         'iso3' => 'KHM',
    //     ],
    //     'selection_criteria' => [
    //         0 => [
    //             0 => [
    //                 'condition_string' => 'true',
    //                 'field_string' => 'disabled',
    //                 'id_field' => 1,
    //                 'target' => 'Beneficiary',
    //                 'table_string' => 'vulnerabilityCriteria',
    //                 'weight' => 1,
    //                 'group' => 0,
    //             ],
    //         ],
    //     ],
    //     'target_type' => AssistanceTargetType::INDIVIDUAL,
    //     'assistance_type' => AssistanceType::DISTRIBUTION,
    //     'sector' => SectorEnum::FOOD_SECURITY,
    //     'subsector' => SubSectorEnum::IN_KIND_FOOD,
    //     'threshold' => 1,
    //     'allowedProductCategoryTypes' => [ProductCategoryType::FOOD],
    //     'foodLimit' => '15',
    // ];

    private $distributionService;

    private $kernel;

    /** @var Countries */
    private $countries;

    /**
     * @var LocationRepository
     */
    private $locationRepository;

    /**
     * @var ModalityRepository
     */
    private $modalityRepository;

    /**
     * @var InstitutionRepository
     */
    private $institutionRepository;

    /**
     * @var AssistanceFactory
     */
    private $assistanceFactory;

    /**
     * @var CommunityRepository
     */
    private $communityRepository;

    /**
     * @var ModalityTypeRepository
     */
    private $modalityTypeRepository;

    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    /**
     * @var AssistanceRepository
     */
    private $assistanceRepository;

    public function __construct(
        Kernel                 $kernel,
        Countries              $countries,
        AssistanceService      $distributionService,
        AssistanceFactory      $assistanceFactory,
        LocationRepository     $locationRepository,
        ModalityRepository     $modalityRepository,
        InstitutionRepository  $institutionRepository,
        CommunityRepository    $communityRepository,
        ModalityTypeRepository $modalityTypeRepository,
        ProjectRepository      $projectRepository,
        AssistanceRepository   $assistanceRepository
    ) {
        $this->distributionService = $distributionService;
        $this->kernel = $kernel;
        $this->countries = $countries;
        $this->assistanceFactory = $assistanceFactory;
        $this->locationRepository = $locationRepository;
        $this->modalityRepository = $modalityRepository;
        $this->institutionRepository = $institutionRepository;
        $this->communityRepository = $communityRepository;
        $this->modalityTypeRepository = $modalityTypeRepository;
        $this->projectRepository = $projectRepository;
        $this->assistanceRepository = $assistanceRepository;
    }

    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     *
     * @throws CsvParserException
     * @throws EntityNotFoundException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->kernel->getEnvironment()) {
            return;
        }

        srand(42);

        /**
         * @var $user User
         */
        $user = $this->getReference('user_admin');

        $projects = $this->projectRepository->findAll();
        foreach ($projects as $project) {
            echo $project->getName()." ";
            $country = $this->countries->getCountry($project->getIso3());
            $this->loadCommonIndividualAssistance($country, $project);
            $this->loadCommonHouseholdAssistance($country, $project);
            $this->loadCommonInstitutionAssistance($country, $project);
            $this->loadCommonCommunityAssistance($country, $project);
            $this->loadSmartcardAssistance($project);
            echo "\n";
        }

        $khmProjects = $this->projectRepository->findBy(['iso3' => 'KHM'], ['id' => 'asc']);
        $khmKhrAssistance = $this->loadSmartcardAssistance($khmProjects[0], 'KHR');
        $khmKhrAssistance->validate($user);
        $this->assistanceRepository->save($khmKhrAssistance);
        $this->setReference(self::REF_SMARTCARD_ASSISTANCE_KHM_KHR, $khmKhrAssistance->getAssistanceRoot());

        $khmUsdAssistance = $this->loadSmartcardAssistance($khmProjects[1], 'USD');
        $khmUsdAssistance->validate($user);
        $this->assistanceRepository->save($khmUsdAssistance);
        $this->setReference(self::REF_SMARTCARD_ASSISTANCE_KHM_USD, $khmUsdAssistance->getAssistanceRoot());

        $syrProjects = $this->projectRepository->findBy(['iso3' => 'SYR'], ['id' => 'asc']);
        $syrSypAssistance = $this->loadSmartcardAssistance($syrProjects[0], 'SYP');
        $syrSypAssistance->validate($user);
        $this->assistanceRepository->save($syrSypAssistance);
        $this->setReference(self::REF_SMARTCARD_ASSISTANCE_SYR_SYP, $syrSypAssistance->getAssistanceRoot());

        $syrUsdAssistance = $this->loadSmartcardAssistance($syrProjects[1], 'USD');
        $syrUsdAssistance->validate($user);
        $this->assistanceRepository->save($syrUsdAssistance);
        $this->setReference(self::REF_SMARTCARD_ASSISTANCE_SYR_USD, $syrUsdAssistance->getAssistanceRoot());
    }

    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
            BeneficiaryFixtures::class,
            BeneficiaryTestFixtures::class,
            ModalityFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['test'];
    }

    /**
     * @param Country $country
     * @param Project $project
     *
     * @return void
     * @throws CsvParserException
     * @throws EntityNotFoundException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    private function loadCommonIndividualAssistance(Country $country, Project $project)
    {
        /**
         * @var Modality $modality
         */
        foreach($this->modalityRepository->findAll() as $modality)
        {
            $assistanceInput = $this->buildAssistanceInputType($country, $project);
            $assistanceInput->setTarget(AssistanceTargetType::INDIVIDUAL);
            $commodity = $this->buildCommoditiesType($country, $modality);
            $commodity->setDivision(null);
            $assistanceInput->addCommodity($commodity);
            $assistance = $this->assistanceFactory->create($assistanceInput);
            $this->assistanceRepository->save($assistance);
            echo "Bx".count($assistance->getBeneficiaries());
        }
    }

    /**
     * @param Country $country
     * @param Project $project
     *
     * @return AssistanceCreateInputType
     */
    private function buildAssistanceInputType(Country $country, Project $project): AssistanceCreateInputType
    {
        $expirationDate = DateTimeImmutable::createFromMutable($project->getEndDate());
        $assistanceInputType = new AssistanceCreateInputType();
        $assistanceInputType->setIso3($country->getIso3());
        $assistanceInputType->setDateDistribution($expirationDate->modify('-2 Days')->format('Y-m-d'));
        $assistanceInputType->setDateExpiration($expirationDate->modify('-1 Day')->format('Y-m-d'));
        $assistanceInputType->setProjectId($project->getId());
        $assistanceInputType->setLocationId($this->getRandomLocation($country->getIso3())->getId());
        $assistanceInputType->setType(AssistanceType::DISTRIBUTION);
        $assistanceInputType->setSector(SectorEnum::FOOD_SECURITY);
        $assistanceInputType->setSubsector(SubSectorEnum::CASH_TRANSFERS);
        $assistanceInputType->setAllowedProductCategoryTypes([ProductCategoryType::FOOD]);
        $assistanceInputType->setThreshold(1);
        $assistanceInputType->setFoodLimit(15);

        return $assistanceInputType;
    }

    /**
     * @param Country               $country
     * @param Modality|ModalityType $modality
     *
     * @return CommodityInputType
     */
    private function buildCommoditiesType(Country $country, $modality): CommodityInputType
    {
        $commodityType = new CommodityInputType();
        $commodityType->setDescription('autogenerated by fixtures');
        $commodityType->setModalityType($modality instanceof Modality ?
            $modality->getModalityTypes()[0]->getName() : $modality->getName());
        $commodityType->setUnit($country->getCurrency());
        $commodityType->setValue(42);

        return $commodityType;
    }

    private function getRandomLocation(string $iso3): Location
    {
        $locations = $this->locationRepository->getByCountry($iso3);
        $count = count($locations);
        if($count === 0) {
            throw new LogicException("There is no location in country $iso3");
        }

        return $locations[ValueGenerator::int(0, $count - 1)];
    }

    /**
     * @param Country $country
     * @param Project $project
     *
     * @return void
     * @throws CsvParserException
     * @throws EntityNotFoundException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    private function loadCommonHouseholdAssistance(Country $country, Project $project)
    {
        /**
         * @var Modality $modality
         */
        foreach($this->modalityRepository->findAll() as $modality)
        {
            $assistanceInput = $this->buildAssistanceInputType($country, $project);
            $assistanceInput->setTarget(AssistanceTargetType::HOUSEHOLD);

            $commodity = $this->buildCommoditiesType($country, $modality);
            if($modality === \NewApiBundle\Enum\ModalityType::CASH) {
                $commodity->setDivision($this->buildDivisionInputType());
            }
            $assistanceInput->addCommodity($commodity);

            $assistance = $this->assistanceFactory->create($assistanceInput);
            $this->assistanceRepository->save($assistance);
            echo "Hx".count($assistance->getBeneficiaries());
        }

        /*
        $data = $this->assistanceArray;
        $data['project']['id'] = $project->getId();
        $data['target_type'] = AssistanceTargetType::HOUSEHOLD;
        $data['location'] = $this->randomLocation($project->getIso3());
        $data['date_distribution'] = $this->randomDate();
        $data['selection_criteria'] = [];
        $data['date_expiration'] = $project->getEndDate()->format('d-m-Y');

        $country = $this->countries->getCountry($project->getIso3());
        foreach ($this->getCommodities($country) as $commodityArray) {
            $data['commodities'] = [0 => $commodityArray];
            $receivers = $this->distributionService->createFromArray($project->getIso3(), $data)['data'];
            echo "Hx".count($receivers);
        }
        */
    }

    private function buildDivisionInputType(): DivisionInputType
    {
        $divisionInputType = new DivisionInputType();
        switch (ValueGenerator::int(0, 1)) {
            case 0:
                $divisionInputType->setCode(CommodityDivision::PER_HOUSEHOLD);
                break;
            case 1:
                $divisionInputType->setCode(CommodityDivision::PER_HOUSEHOLD_MEMBER);
                break;
        }

        return $divisionInputType;
    }

    /**
     * @param Country $country
     * @param Project $project
     *
     * @return void
     * @throws CsvParserException
     * @throws EntityNotFoundException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    private function loadCommonInstitutionAssistance(Country $country, Project $project)
    {
        $unarchivedInstitutions = $this->institutionRepository->getUnarchivedByProject($project);
        $institutions = array_map(function (Institution $institution) {
            return $institution->getId();
        }, $unarchivedInstitutions);

        /**
         * @var Modality $modality
         */
        foreach($this->modalityRepository->findAll() as $modality)
        {
            $assistanceInput = $this->buildAssistanceInputType($country, $project);
            $assistanceInput->setTarget(AssistanceTargetType::INSTITUTION);
            $assistanceInput->setInstitutions($institutions);
            $commodity = $this->buildCommoditiesType($country, $modality);
            $commodity->setDivision(null);
            $assistanceInput->addCommodity($commodity);
            $assistance = $this->assistanceFactory->create($assistanceInput);
            $this->assistanceRepository->save($assistance);
            echo "Ix".count($assistance->getBeneficiaries());
        }

        /*
        $data = $this->assistanceArray;
        $data['project']['id'] = $project->getId();
        $data['target_type'] = AssistanceTargetType::INSTITUTION;
        $data['location'] = $this->randomLocation($project->getIso3());
        $data['date_distribution'] = $this->randomDate();
        $data['date_expiration'] = $project->getEndDate()->format('d-m-Y');
        unset($data['selection_criteria']);

        $data['institutions'] = [];
        $institutions = $manager->getRepository(Institution::class)->getUnarchivedByProject($project);
        if (empty($institutions)) {
            echo '(no I)';
            return;
        }
        $data['institutions'] = array_map(function (Institution $institution) { return $institution->getId(); }, $institutions);

        $country = $this->countries->getCountry($project->getIso3());
        foreach ($this->getCommodities($country) as $commodityArray) {
            $data['commodities'] = [0 => $commodityArray];
            $receivers = $this->distributionService->createFromArray($project->getIso3(), $data)['data'];
            echo "Ix".count($receivers);
        }
        */
    }

    /**
     * @param Country $country
     * @param Project $project
     *
     * @return void
     * @throws CsvParserException
     * @throws EntityNotFoundException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    private function loadCommonCommunityAssistance(Country $country, Project $project)
    {
        $unarchivedCommunities = $this->communityRepository->getUnarchivedByProject($project);
        $communities = array_map(function(Community $community) {
            return $community->getId();
        }, $unarchivedCommunities);

        /**
         * @var Modality $modality
         */
        foreach($this->modalityRepository->findAll() as $modality)
        {
            $assistanceInput = $this->buildAssistanceInputType($country, $project);
            $assistanceInput->setTarget(AssistanceTargetType::COMMUNITY);
            $assistanceInput->setCommunities($communities);
            $commodity = $this->buildCommoditiesType($country, $modality);
            $commodity->setDivision(null);
            $assistanceInput->addCommodity($commodity);
            $assistance = $this->assistanceFactory->create($assistanceInput);
            $this->assistanceRepository->save($assistance);
            echo "Cx".count($assistance->getBeneficiaries());
        }
/*
        $data = $this->assistanceArray;
        $data['project']['id'] = $project->getId();
        $data['target_type'] = AssistanceTargetType::COMMUNITY;
        $data['location'] = $this->randomLocation($project->getIso3());
        $data['date_distribution'] = $this->randomDate();
        $data['date_expiration'] = $project->getEndDate()->format('d-m-Y');
        unset($data['selection_criteria']);

        $data['communities'] = [];
        $communities = $manager->getRepository(Community::class)->getUnarchivedByProject($project)->getQuery()->getResult();
        if (empty($communities)) {
            echo '(no C)';
            return;
        }
        $data['communities'] = array_map(function (Community $community) { return $community->getId(); }, $communities);

        $country = $this->countries->getCountry($project->getIso3());
        foreach ($this->getCommodities($country) as $commodityArray) {
            $data['commodities'] = [0 => $commodityArray];
            $receivers = $this->distributionService->createFromArray($project->getIso3(), $data)['data'];
            echo "Cx".count($receivers);
        }
*/
    }

    private function buildSelectionCriteriaInputType(): SelectionCriterionInputType
    {
        $selectionCriteriaType = new SelectionCriterionInputType();
        $selectionCriteriaType->setCondition('=');
        $selectionCriteriaType->setField('gender');
        $selectionCriteriaType->setTarget('Beneficiary');
        $selectionCriteriaType->setGroup(0);
        $selectionCriteriaType->setWeight(1);
        $selectionCriteriaType->setValue('0');

        return $selectionCriteriaType;
    }

    /**
     * @param Project     $project
     * @param string|null $currency
     *
     * @return Assistance
     * @throws CsvParserException
     * @throws EntityNotFoundException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    private function loadSmartcardAssistance(Project $project, ?string $currency = null): Assistance
    {
        $country = $this->countries->getCountry($project->getIso3());
        $modality = $this->modalityTypeRepository->findOneBy(['name' => 'Smartcard'], ['id' => 'asc']);
        $assistanceInputType = $this->buildAssistanceInputType($country, $project);
        $assistanceInputType->setTarget(AssistanceTargetType::INDIVIDUAL);
        $commodityInputType = $this->buildCommoditiesType($country, $modality);
        $commodityInputType->setValue(45);
        if ($currency) {
            $commodityInputType->setUnit($currency);
        }
        $assistanceInputType->addCommodity($commodityInputType);
        $assistanceInputType->addSelectionCriterion($this->buildSelectionCriteriaInputType());

        $assistance = $this->assistanceFactory->create($assistanceInputType);
        $this->assistanceRepository->save($assistance);

        return $assistance;

/*
        $data = $this->assistanceArray;
        $data['project']['id'] = $project->getId();
        $data['commodities'] = [
            0 => [
                'modality' => 'Cash',
                'modality_type' => [
                    'id' => $modality->getId(),
                ],
                'type' => 'Smartcard',
                'unit' => $currency,
                'value' => 45,
                'description' => null,
            ],
        ];
        $data['selection_criteria'] = [
            0 => [
                0 => [
                    'condition_string' => '=',
                    'field_string' => 'gender',
                    'table_string' => 'Personnal',
                    'target' => 'Beneficiary',
                    'type' => 'table_field',
                    'value_string' => '0',
                    'weight' => 1,
                    'group' => 0,
                ],
            ],
        ];
        $data['date_expiration'] = $project->getEndDate()->format('d-m-Y');

        return $this->distributionService->createFromArray($project->getIso3(), $data, 1)['distribution'];
    */
    }

    private function getCommodities(Country $country): array
    {
        $modalities = $this->modalityRepository->findAll();
        $commodities = [];
        foreach ($modalities as $modality) {
            $modalityType = $modality->getModalityTypes()[0];
            $commodities[] = [
                'modality' => $modalityType->getModality()->getName(),
                'modality_type' => [
                    'id' => $modalityType->getId(),
                ],
                'type' => $modalityType->getModality()->getId(),
                'unit' => $country->getCurrency(),
                'value' => 42,
                'description' => 'autogenerated by fixtures',
            ];
        }


        return $commodities;
    }

    private function randomLocation(string $countryIso3): array
    {
        $mapper = new LocationMapper();
        $locationArray = ['country_iso3' => $countryIso3];

        $entities = $this->locationRepository->getByCountry($countryIso3);
        if (0 === count($entities)) {
            return $locationArray;
        }

        $i = rand(0, count($entities) - 1);

        return array_merge($mapper->toFlatArray($entities[$i]), $locationArray);
    }

    private function randomDate(): string
    {
        $date = new DateTime();
        $date->modify('+100 days');
        $date->modify('-'.rand(1, 200).' days');
        return $date->format('d-m-Y');
    }
}
