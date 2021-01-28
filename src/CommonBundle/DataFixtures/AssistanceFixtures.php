<?php

namespace CommonBundle\DataFixtures;

use DistributionBundle\DBAL\AssistanceTypeEnum;
use DistributionBundle\Entity\Assistance;
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
            $this->loadCommonIndividualAssistance($manager, $project);
            $this->loadCommonHouseholdAssistance($manager, $project);
            $this->loadSmartcardAssistance($manager, $project);
        }
    }

    public function getDependencies()
    {
        return [
            ProjectFixtures::class,
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

        $this->distributionService->create($project->getIso3(), $data, 1);
    }

    private function loadCommonHouseholdAssistance(ObjectManager $manager, Project $project)
    {
        $data = $this->assistanceArray;
        $data['project']['id'] = $project->getId();
        $data['target_type'] = AssistanceTargetType::HOUSEHOLD;

        $this->distributionService->create($project->getIso3(), $data, 1);
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
}
