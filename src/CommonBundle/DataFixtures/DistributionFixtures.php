<?php


namespace CommonBundle\DataFixtures;

use DistributionBundle\Utils\DistributionService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\Kernel;

class DistributionFixtures extends Fixture
{
    private $distributionArray = [
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
                'value' => '45'
            ]
        ],
        'date_distribution' => '13-09-2020',
        'location' => [
            'adm1' => 1,
            'adm2' => 1,
            'adm3' => '',
            'adm4' => '',
            'country_iso3' => 'KHM'
        ],
        'location_name' => '',
        'name' => 'Battambang-9/13/2018',
        'project' => [
            'donors' => [],
            'donors_name' => [],
            'id' => '1',
            'name' => '',
            'sectors' => [],
            'sectors_name' => [],
        ],
        'selection_criteria' => [
            0 => [
                'condition_string' => 'true',
                'field_string' => 'disabled',
                'id_field' => 1,
                'kind_beneficiary' => 'Beneficiary',
                'table_string' => 'vulnerabilityCriteria',
                'weight' => '1'
            ]
        ],
        'type' => 'Individual',
        'threshold' => '1'
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
            $this->distributionService->create("KHM", $this->distributionArray, 1);
        }
    }
}
