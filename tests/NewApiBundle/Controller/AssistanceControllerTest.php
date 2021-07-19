<?php

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Community;
use CommonBundle\Entity\Location;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\ModalityType;
use DistributionBundle\Enum\AssistanceType;
use Exception;
use ProjectBundle\Entity\Project;
use Tests\BMSServiceTestCase;

class AssistanceControllerTest extends BMSServiceTestCase
{
    /**
     * @throws Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    public function testGetItem()
    {
        /** @var Assistance $assistance */
        $assistance = self::$container->get('doctrine')->getRepository(Assistance::class)->findBy([])[0];
        $commodityIds = array_map(function (\DistributionBundle\Entity\Commodity $commodity) {
            return $commodity->getId();
        }, $assistance->getCommodities()->toArray());

        $this->request('GET', '/api/basic/web-app/v1/assistances/'.$assistance->getId());

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "id": '.$assistance->getId().',
            "name": "'.$assistance->getName().'",
            "dateDistribution": "'.$assistance->getDateDistribution()->format(\DateTime::ISO8601).'",
            "dateExpiration": "*",
            "projectId": '.$assistance->getProject()->getId().',
            "locationId": '.$assistance->getLocation()->getId().',
            "target": "'.$assistance->getTargetType().'",
            "type": "'.$assistance->getAssistanceType().'",
            "sector": "'.$assistance->getSector().'",
            "subsector": "*",
            "householdsTargeted": '.($assistance->getHouseholdsTargeted() ?: 'null').',
            "individualsTargeted": '.($assistance->getIndividualsTargeted() ?: 'null').',
            "description": "*",
            "commodityIds": ['.implode(',', $commodityIds).'],
            "validated": '.($assistance->getValidated() ? 'true' : 'false').',
            "completed": '.($assistance->getCompleted() ? 'true' : 'false').',
            "deletable": '.($assistance->getValidated() ? 'false' : 'true').'
        }', $this->client->getResponse()->getContent());
    }

    public function testList()
    {
        /** @var Project $project */
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([])[0];
        /** @var Location $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([])[0];

        $this->request('GET', '/api/basic/web-app/v1/assistances?filter[type]='.AssistanceType::DISTRIBUTION.
                                                    '&filter[modalityTypes][]=Smartcard'.
                                                    '&filter[projects][]='.$project->getId().
                                                    '&filter[locations][]='.$location->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testAsisstancesByProject()
    {
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([])[0];

        $this->request('GET', '/api/basic/web-app/v1/projects/'.$project->getId().'/assistances');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testCreateDistribution()
    {
        /** @var Project $project */
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([])[0];

        /** @var Location $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([])[0];

        /** @var ModalityType $modalityType */
        $modalityType = self::$container->get('doctrine')->getRepository(ModalityType::class)->findBy(['name' => 'Cash'])[0];

        $this->request('POST', '/api/basic/web-app/v1/assistances', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2021-03-10T13:45:32.988Z',
            'sector' => \ProjectBundle\DBAL\SectorEnum::FOOD_SECURITY,
            'subsector' => \ProjectBundle\DBAL\SubSectorEnum::FOOD_CASH_FOR_WORK,
            'type' => AssistanceType::DISTRIBUTION,
            'target' => \DistributionBundle\Enum\AssistanceTargetType::INDIVIDUAL,
            'threshold' => 1,
            'commodities' => [
                ['modalityType' => $modalityType->getName(), 'unit' => 'CZK', 'value' => 1000],
            ],
            'selectionCriteria' => [
                [
                    'group' => 1,
                    'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::BENEFICIARY,
                    'field' => 'dateOfBirth',
                    'condition' => '<',
                    'weight' => 1,
                    'value' => '2020-01-01',
                ],
            ],
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "id": "*",
            "name": "*",
            "dateDistribution": "*",
            "dateExpiration": null,
            "projectId": "*",
            "locationId": "*",
            "target": "*",
            "type": "*",
            "sector": "*",
            "subsector": "*",
            "householdsTargeted": "*",
            "individualsTargeted": "*",
            "description": "*",
            "commodityIds": ["*"]
        }', $this->client->getResponse()->getContent());
    }

    public function testCreateDistributionWithExpirationDate()
    {
        /** @var Project $project */
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([])[0];

        /** @var Location $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([])[0];

        /** @var ModalityType $modalityType */
        $modalityType = self::$container->get('doctrine')->getRepository(ModalityType::class)->findBy(['name' => 'Cash'])[0];

        $this->request('POST', '/api/basic/web-app/v1/assistances', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2021-03-10T13:45:32.988Z',
            'dateExpiration' => '2022-10-10T03:45:00.000Z',
            'sector' => \ProjectBundle\DBAL\SectorEnum::FOOD_SECURITY,
            'subsector' => \ProjectBundle\DBAL\SubSectorEnum::FOOD_CASH_FOR_WORK,
            'type' => AssistanceType::DISTRIBUTION,
            'target' => \DistributionBundle\Enum\AssistanceTargetType::INDIVIDUAL,
            'threshold' => 1,
            'commodities' => [
                ['modalityType' => $modalityType->getName(), 'unit' => 'CZK', 'value' => 1000],
            ],
            'selectionCriteria' => [
                [
                    'group' => 1,
                    'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::BENEFICIARY,
                    'field' => 'dateOfBirth',
                    'condition' => '<',
                    'weight' => 1,
                    'value' => '2020-01-01',
                ],
            ],
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "id": "*",
            "name": "*",
            "dateDistribution": "2021-03-10T13:45:32+0000",
            "dateExpiration": "2022-10-10T03:45:00+0000",
            "projectId": "*",
            "locationId": "*",
            "target": "*",
            "type": "*",
            "sector": "*",
            "subsector": "*",
            "householdsTargeted": "*",
            "individualsTargeted": "*",
            "deletable": true,
            "description": "*",
            "commodityIds": ["*"]
        }', $this->client->getResponse()->getContent());
    }

    public function testCreateActivity()
    {
        /** @var Project $project */
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([])[0];

        /** @var Location $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([])[0];

        $this->request('POST', '/api/basic/web-app/v1/assistances', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2000-12-01T01:01:01+00:00',
            'sector' => \ProjectBundle\DBAL\SectorEnum::LIVELIHOODS,
            'subsector' => \ProjectBundle\DBAL\SubSectorEnum::SKILLS_TRAINING,
            'type' => AssistanceType::ACTIVITY,
            'target' => \DistributionBundle\Enum\AssistanceTargetType::INDIVIDUAL,
            'threshold' => 1,
            'selectionCriteria' => [
                [
                    'group' => 1,
                    'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::BENEFICIARY,
                    'field' => 'gender',
                    'condition' => '=',
                    'weight' => 1,
                    'value' => 'F',
                ],
            ],
            'description' => 'test activity',
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "id": "*",
            "name": "*",
            "dateDistribution": "*",
            "dateExpiration": null,
            "projectId": "*",
            "locationId": "*",
            "target": "*",
            "type": "*",
            "sector": "*",
            "subsector": "*",
            "householdsTargeted": "*",
            "individualsTargeted": "*",
            "deletable": true,
            "description": "*",
            "commodityIds": "*"
        }', $this->client->getResponse()->getContent());
    }

    public function testCreateCommunityActivity()
    {
        /** @var Project $project */
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([])[0];

        /** @var Location $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([])[0];

        /** @var Community $community */
        $community = self::$container->get('doctrine')->getRepository(Community::class)->findBy([])[0];

        $this->request('POST', '/api/basic/web-app/v1/assistances', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2000-12-01T01:01:01+0000',
            'sector' => \ProjectBundle\DBAL\SectorEnum::SHELTER,
            'subsector' => \ProjectBundle\DBAL\SubSectorEnum::CONSTRUCTION,
            'type' => AssistanceType::ACTIVITY,
            'target' => \DistributionBundle\Enum\AssistanceTargetType::COMMUNITY,
            'communities' => [$community->getId()],
            'description' => 'test construction activity',
            'householdsTargeted' => 10,
            'individualsTargeted' => null,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "id": "*",
            "name": "*",
            "dateDistribution": "*",
            "dateExpiration": null,
            "projectId": "*",
            "locationId": "*",
            "target": "*",
            "type": "*",
            "sector": "*",
            "subsector": "*",
            "householdsTargeted": "*",
            "individualsTargeted": "*",
            "deletable": true,
            "description": "*",
            "commodityIds": []
        }', $this->client->getResponse()->getContent());
    }
}
