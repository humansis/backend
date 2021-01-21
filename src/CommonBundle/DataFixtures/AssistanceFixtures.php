<?php

namespace CommonBundle\DataFixtures;

use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Institution;
use CommonBundle\Controller\CountryController;
use DistributionBundle\DBAL\AssistanceTypeEnum;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\Modality;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Enum\AssistanceType;
use DistributionBundle\Entity\ModalityType;
use DistributionBundle\Utils\AssistanceService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ProjectBundle\DBAL\SectorEnum;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpKernel\Kernel;

class AssistanceFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    const REF_SMARTCARD_ASSISTANCE = '569f131a-387d-4588-9e17-ecd94f261a85';

    private $assistanceArray = [
        'adm1' => '',
        'adm2' => '',
        'adm3' => '',
        'adm4' => '',
        'commodities' => [
            0 => [
                'modality' => 'CTP',
                'modality_type' => [
                    'id' => 1,
                ],
                'type' => 'Mobile',
                'unit' => 'USD',
                'value' => 45,
                'description' => null,
            ],
        ],
        'date_distribution' => '13-09-2020',
        'location' => [
            'adm1' => 1,
            'adm2' => 1,
            'adm3' => null,
            'adm4' => null,
            'country_iso3' => 'KHM',
        ],
        'location_name' => '',
        'name' => 'Battambang-9/13/2018',
        'project' => [
            'donors' => [],
            'donors_name' => [],
            'id' => '?',
            'name' => '',
            'sectors' => [],
            'sectors_name' => [],
        ],
        'selection_criteria' => [
            0 => [
                0 => [
                    'condition_string' => 'true',
                    'field_string' => 'disabled',
                    'id_field' => 1,
                    'target' => 'Beneficiary',
                    'table_string' => 'vulnerabilityCriteria',
                    'weight' => 1,
                ],
            ],
        ],
        'target_type' => AssistanceTargetType::INDIVIDUAL,
        'assistance_type' => AssistanceType::DISTRIBUTION,
        'sector' => SectorEnum::FOOD_SECURITY,
        'subsector' => null,
        'threshold' => 1,
    ];

    private $distributionService;

    private $kernel;

    public function __construct(Kernel $kernel, AssistanceService $distributionService)
    {
        $this->distributionService = $distributionService;
        $this->kernel = $kernel;
    }

    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     *
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->kernel->getEnvironment()) {
            return;
        }

        $projects = $manager->getRepository(Project::class)->findAll();
        foreach ($projects as $project) {
            echo $project->getName()." ";
            $this->loadCommonIndividualAssistance($manager, $project);
            $this->loadCommonHouseholdAssistance($manager, $project);
            $this->loadCommonInstitutionAssistance($manager, $project);
            $this->loadCommonCommunityAssistance($manager, $project);
            $this->loadSmartcardAssistance($manager, $project);
            echo "\n";
        }
    }

    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
            ModalityFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['test'];
    }

    private function loadCommonIndividualAssistance(ObjectManager $manager, Project $project)
    {
        $data = $this->assistanceArray;
        $data['project']['id'] = $project->getId();
        $data['target_type'] = AssistanceTargetType::INDIVIDUAL;

        $country = CountryController::COUNTRIES[$project->getIso3()];
        foreach ($this->getCommodities($manager, $country) as $commodityArray) {
            $data['commodities'] = [0 => $commodityArray];
            $this->distributionService->create($project->getIso3(), $data);
            echo "B";
        }
    }

    private function loadCommonHouseholdAssistance(ObjectManager $manager, Project $project)
    {
        $data = $this->assistanceArray;
        $data['project']['id'] = $project->getId();
        $data['target_type'] = AssistanceTargetType::HOUSEHOLD;

        $country = CountryController::COUNTRIES[$project->getIso3()];
        foreach ($this->getCommodities($manager, $country) as $commodityArray) {
            $data['commodities'] = [0 => $commodityArray];
            $this->distributionService->create($project->getIso3(), $data);
            echo "H";
        }
    }

    private function loadCommonInstitutionAssistance(ObjectManager $manager, Project $project)
    {
        $data = $this->assistanceArray;
        $data['project']['id'] = $project->getId();
        $data['target_type'] = AssistanceTargetType::INSTITUTION;
        unset($data['selection_criteria']);

        $data['institutions'] = [];
        $institutions = $manager->getRepository(Institution::class)->getUnarchivedByProject($project);
        if (empty($institutions)) {
            echo 'i';
            return;
        }
        $data['institutions'] = array_map(function (Institution $institution) { return $institution->getId(); }, $institutions);

        $country = CountryController::COUNTRIES[$project->getIso3()];
        foreach ($this->getCommodities($manager, $country) as $commodityArray) {
            $data['commodities'] = [0 => $commodityArray];
            $this->distributionService->create($project->getIso3(), $data);
            echo "I";
        }
    }

    private function loadCommonCommunityAssistance(ObjectManager $manager, Project $project)
    {
        $data = $this->assistanceArray;
        $data['project']['id'] = $project->getId();
        $data['target_type'] = AssistanceTargetType::COMMUNITY;
        unset($data['selection_criteria']);

        $data['communities'] = [];
        $communities = $manager->getRepository(Community::class)->getUnarchivedByProject($project)->getQuery()->getResult();
        if (empty($communities)) {
            echo 'c';
            return;
        }
        $data['communities'] = array_map(function (Community $community) { return $community->getId(); }, $communities);

        $country = CountryController::COUNTRIES[$project->getIso3()];
        foreach ($this->getCommodities($manager, $country) as $commodityArray) {
            $data['commodities'] = [0 => $commodityArray];
            $this->distributionService->create($project->getIso3(), $data);
            echo "C";
        }
    }

    private function loadSmartcardAssistance(ObjectManager $manager, Project $project)
    {
        $modalityType = $manager->getRepository(ModalityType::class)->findOneBy(['name' => 'Smartcard']);

        $data = $this->assistanceArray;
        $data['project']['id'] = $project->getId();
        $data['commodities'] = [
            0 => [
                'modality' => 'Cash',
                'modality_type' => [
                    'id' => $modalityType->getId(),
                ],
                'type' => 'Smartcard',
                'unit' => 'USD',
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
                ],
            ],
        ];

        $result = $this->distributionService->create($project->getIso3(), $data, 1);

        $this->setReference(self::REF_SMARTCARD_ASSISTANCE, $result['distribution']);
    }

    private function getCommodities(ObjectManager $manager, array $country): array
    {
        $modalities = $manager->getRepository(Modality::class)->findAll();
        $commodities = [];
        foreach ($modalities as $modality) {
            foreach ($modality->getModalityTypes() as $modalityType) {
                $commodities[] = [
                    'modality' => $modalityType->getModality()->getName(),
                    'modality_type' => [
                        'id' => $modalityType->getId(),
                    ],
                    'type' => $modalityType->getModality()->getId(),
                    'unit' => $country['currency'],
                    'value' => 42,
                    'description' => 'autogenerated by fixtures',
                ];
                if ($modality->getName() === 'In Kind') continue 2;
            }
        }

        return $commodities;
    }
}
