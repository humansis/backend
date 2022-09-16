<?php

namespace DataFixtures;

use Component\Assistance\AssistanceFactory;
use Component\Assistance\Domain\Assistance;
use Component\Assistance\Enum\CommodityDivision;
use Component\Country\Countries;
use Component\Country\Country;
use DataFixtures\Beneficiaries\BeneficiaryFixtures;
use DateTimeImmutable;
use DBAL\SectorEnum;
use DBAL\SubSectorEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectManager;
use Entity\Community;
use Entity\Institution;
use Entity\Location;
use Entity\Project;
use Entity\User;
use Enum\AssistanceTargetType;
use Enum\AssistanceType;
use Enum\Modality;
use Enum\ModalityType;
use Enum\ProductCategoryType;
use Exception\CsvParserException;
use InputType\Assistance\CommodityInputType;
use InputType\Assistance\DivisionInputType;
use InputType\Assistance\SelectionCriterionInputType;
use InputType\AssistanceCreateInputType;
use LogicException;
use Repository\AssistanceRepository;
use Repository\CommunityRepository;
use Repository\InstitutionRepository;
use Repository\LocationRepository;
use Repository\ModalityRepository;
use Repository\ModalityTypeRepository;
use Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Kernel;
use Utils\ValueGenerator\ValueGenerator;

class AssistanceFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    const REF_SMARTCARD_ASSISTANCE_KHM_KHR = '569f131a-387d-4588-9e17-ecd94f261a85';
    const REF_SMARTCARD_ASSISTANCE_KHM_USD = '9ab17087-f54f-41ee-9b8d-c91d932d8ec2';
    const REF_SMARTCARD_ASSISTANCE_SYR_SYP = 'e643bdbc-df6f-449a-b424-8c842a408e47';
    const REF_SMARTCARD_ASSISTANCE_SYR_USD = '223b91e8-0f05-44b4-9c74-f156cbd95d1a';

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
        AssistanceFactory      $assistanceFactory,
        LocationRepository     $locationRepository,
        ModalityRepository     $modalityRepository,
        InstitutionRepository  $institutionRepository,
        CommunityRepository    $communityRepository,
        ModalityTypeRepository $modalityTypeRepository,
        ProjectRepository      $projectRepository,
        AssistanceRepository   $assistanceRepository
    ) {
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
            $country = $this->countries->getCountry($project->getCountryIso3());
            $this->loadCommonIndividualAssistance($country, $project);
            $this->loadCommonHouseholdAssistance($country, $project);
            $this->loadCommonInstitutionAssistance($country, $project);
            $this->loadCommonCommunityAssistance($country, $project);
            $this->loadSmartcardAssistance($project);
            echo "\n";
        }

        $khmProjects = $this->projectRepository->findBy(['countryIso3' => 'KHM'], ['id' => 'asc']);
        $khmKhrAssistance = $this->loadSmartcardAssistance($khmProjects[0], 'KHR');
        $this->validateAssistance($khmKhrAssistance, $user);
        $this->setReference(self::REF_SMARTCARD_ASSISTANCE_KHM_KHR, $khmKhrAssistance->getAssistanceRoot());

        $khmUsdAssistance = $this->loadSmartcardAssistance($khmProjects[1], 'USD');
        $this->validateAssistance($khmUsdAssistance, $user);
        $this->setReference(self::REF_SMARTCARD_ASSISTANCE_KHM_USD, $khmUsdAssistance->getAssistanceRoot());

        $syrProjects = $this->projectRepository->findBy(['countryIso3' => 'SYR'], ['id' => 'asc']);
        $syrSypAssistance = $this->loadSmartcardAssistance($syrProjects[0], 'SYP');
        $this->validateAssistance($syrSypAssistance, $user);
        $this->setReference(self::REF_SMARTCARD_ASSISTANCE_SYR_SYP, $syrSypAssistance->getAssistanceRoot());

        $syrUsdAssistance = $this->loadSmartcardAssistance($syrProjects[1], 'USD');
        $this->validateAssistance($syrUsdAssistance, $user);
        $this->setReference(self::REF_SMARTCARD_ASSISTANCE_SYR_USD, $syrUsdAssistance->getAssistanceRoot());
    }

    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
            BeneficiaryFixtures::class,
            BeneficiaryTestFixtures::class,
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
        foreach (Modality::values() as $modality) {
            $assistanceInput = $this->buildAssistanceInputType($country, $project);
            $assistanceInput->setTarget(AssistanceTargetType::INDIVIDUAL);
            $commodity = $this->buildCommoditiesType($country, $modality);
            $commodity->setDivision(null);
            $assistanceInput->addCommodity($commodity);
            $assistanceInput->addSelectionCriterion($this->buildSelectionCriteriaInputType());
            $assistance = $this->assistanceFactory->create($assistanceInput);
            $this->assistanceRepository->save($assistance);
            echo "Bx".count($assistance->getBeneficiaries());
        }
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
        foreach (Modality::values() as $modality) {
            $assistanceInput = $this->buildAssistanceInputType($country, $project);
            $assistanceInput->setTarget(AssistanceTargetType::HOUSEHOLD);

            $commodity = $this->buildCommoditiesType($country, $modality);
            if ($modality === ModalityType::CASH) {
                $commodity->setDivision($this->buildDivisionInputType());
            }
            $assistanceInput->addCommodity($commodity);
            $assistanceInput->addSelectionCriterion($this->buildSelectionCriteriaInputType());

            $assistance = $this->assistanceFactory->create($assistanceInput);
            $this->assistanceRepository->save($assistance);
            echo "Hx".count($assistance->getBeneficiaries());
        }
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
        foreach (Modality::values() as $modality) {
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
        $communities = array_map(function (Community $community) {
            return $community->getId();
        }, $unarchivedCommunities);

        /**
         * @var Modality $modality
         */
        foreach (Modality::values() as $modality) {
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
        $country = $this->countries->getCountry($project->getCountryIso3());
        $assistanceInputType = $this->buildAssistanceInputType($country, $project);
        $assistanceInputType->setTarget(AssistanceTargetType::INDIVIDUAL);
        $commodityInputType = $this->buildCommoditiesType($country, ModalityType::SMART_CARD);
        $commodityInputType->setValue(45);
        if ($currency) {
            $commodityInputType->setUnit($currency);
        }
        $assistanceInputType->addCommodity($commodityInputType);
        $assistanceInputType->addSelectionCriterion($this->buildSelectionCriteriaInputType());

        $assistance = $this->assistanceFactory->create($assistanceInputType);
        $this->assistanceRepository->save($assistance);

        return $assistance;
    }

    /**
     * @param Assistance $assistance
     * @param User       $user
     *
     * @return void
     */
    private function validateAssistance(Assistance $assistance, User $user): void
    {
        $assistance->validate($user);
        $this->assistanceRepository->save($assistance);
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
        $commodityType->setModalityType($modality);
        $commodityType->setUnit($country->getCurrency());
        $commodityType->setValue(42);

        return $commodityType;
    }

    private function getRandomLocation(string $iso3): Location
    {
        $locations = $this->locationRepository->getByCountry($iso3);
        $count = count($locations);
        if ($count === 0) {
            throw new LogicException("There is no location in country $iso3");
        }

        return $locations[ValueGenerator::int(0, $count - 1)];
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
}
