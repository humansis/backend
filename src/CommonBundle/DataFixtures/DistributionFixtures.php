<?php


namespace CommonBundle\DataFixtures;

use DistributionBundle\DBAL\AssistanceTypeEnum;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Utils\DistributionService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpKernel\Kernel;

class DistributionFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private $distributionArray = [
        'adm1' => '',
        'adm2' => '',
        'adm3' => '',
        'adm4' => '',
        'type' => Assistance::TYPE_BENEFICIARY,
        'commodities' => [
            0 => [
                'modality' => 'CTP',
                'modality_type' => [
                    'id' => 1,
                ],
                'type' => 'Mobile',
                'unit' => 'USD',
                'value' => 45,
                'description' => null
            ]
        ],
        'date_distribution' => '13-09-2020',
        'location' => [
            'adm1' => 1,
            'adm2' => 1,
            'adm3' => null,
            'adm4' => null,
            'country_iso3' => 'KHM'
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
                'condition_string' => 'true',
                'field_string' => 'disabled',
                'id_field' => 1,
                'target' => 'Beneficiary',
                'table_string' => 'vulnerabilityCriteria',
                'weight' => 1
            ]
        ],
        'target_type' => Assistance::TYPE_BENEFICIARY,
        'assistance_type' => AssistanceTypeEnum::DISTRIBUTION,
        'threshold' => 1,
    ];

    private $distributionService;
    private $kernel;

    public function __construct(Kernel $kernel, DistributionService $distributionService)
    {
        $this->distributionService = $distributionService;
        $this->kernel = $kernel;
    }


    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function load(ObjectManager $manager)
    {
        if ($this->kernel->getEnvironment() !== "prod") {
            $project = $manager->getRepository(Project::class)->findOneBy(['iso3' => 'KHM']);
            $this->distributionArray['project']['id'] = $project->getId();

            $this->distributionService->create("KHM", $this->distributionArray, 1);
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
}
