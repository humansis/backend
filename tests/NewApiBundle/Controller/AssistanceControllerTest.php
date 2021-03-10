<?php

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Community;
use CommonBundle\Entity\Location;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\ModalityType;
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
        $this->client = $this->container->get('test.client');
    }

    public function testGetItem()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var Assistance $assistance */
        $assistance = $this->container->get('doctrine')->getRepository(Assistance::class)->findBy([])[0];
        $commodityIds = array_map(function (\DistributionBundle\Entity\Commodity $commodity) {
            return $commodity->getId();
        }, $assistance->getCommodities()->toArray());

        $this->request('GET', '/api/basic/assistances/'.$assistance->getId());

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "id": '.$assistance->getId().',
            "name": "'.$assistance->getName().'",
            "dateDistribution": "'.$assistance->getDateDistribution()->format('Y-m-d').'",
            "projectId": '.$assistance->getProject()->getId().',
            "locationId": '.$assistance->getLocation()->getId().',
            "target": "'.$assistance->getTargetType().'",
            "type": "'.$assistance->getAssistanceType().'",
            "commodityIds": ['.implode(',', $commodityIds).']
        }', $this->client->getResponse()->getContent());
    }

    public function testList()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/assistances');

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
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $project = $this->container->get('doctrine')->getRepository(Project::class)->findBy([])[0];

        $this->request('GET', '/api/basic/projects/'.$project->getId().'/assistances');

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
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var Project $project */
        $project = $this->container->get('doctrine')->getRepository(Project::class)->findBy([])[0];

        /** @var Location $location */
        $location = $this->container->get('doctrine')->getRepository(Location::class)->findBy([])[0];

        /** @var ModalityType $modalityType */
        $modalityType = $this->container->get('doctrine')->getRepository(ModalityType::class)->findBy(['name' => 'Cash'])[0];

        $this->request('POST', '/api/basic/assistances', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2021-03-10T13:45:32.988Z',
            'sector' => \ProjectBundle\DBAL\SectorEnum::FOOD_SECURITY,
            'subsector' => \ProjectBundle\DBAL\SubSectorEnum::FOOD_CASH_FOR_WORK,
            'type' => \DistributionBundle\Enum\AssistanceType::DISTRIBUTION,
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
            "projectId": "*",
            "locationId": "*",
            "target": "*",
            "type": "*",
            "commodityIds": ["*"]
        }', $this->client->getResponse()->getContent());
    }

    public function testCreateActivity()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var Project $project */
        $project = $this->container->get('doctrine')->getRepository(Project::class)->findBy([])[0];

        /** @var Location $location */
        $location = $this->container->get('doctrine')->getRepository(Location::class)->findBy([])[0];

        $this->request('POST', '/api/basic/assistances', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2000-12-01T01:01:01+00:00',
            'sector' => \ProjectBundle\DBAL\SectorEnum::LIVELIHOODS,
            'subsector' => \ProjectBundle\DBAL\SubSectorEnum::SKILLS_TRAINING,
            'type' => \DistributionBundle\Enum\AssistanceType::ACTIVITY,
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
            "projectId": "*",
            "locationId": "*",
            "target": "*",
            "type": "*",
            "commodityIds": ["*"]
        }', $this->client->getResponse()->getContent());
    }

    public function testCreateCommunityActivity()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var Project $project */
        $project = $this->container->get('doctrine')->getRepository(Project::class)->findBy([])[0];

        /** @var Location $location */
        $location = $this->container->get('doctrine')->getRepository(Location::class)->findBy([])[0];

        /** @var Community $community */
        $community = $this->container->get('doctrine')->getRepository(Community::class)->findBy([])[0];

        $this->request('POST', '/api/basic/assistances', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2000-12-01T01:01:01+0000',
            'sector' => \ProjectBundle\DBAL\SectorEnum::SHELTER,
            'subsector' => \ProjectBundle\DBAL\SubSectorEnum::CONSTRUCTION,
            'type' => \DistributionBundle\Enum\AssistanceType::ACTIVITY,
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
            "projectId": "*",
            "locationId": "*",
            "target": "*",
            "type": "*",
            "commodityIds": []
        }', $this->client->getResponse()->getContent());
    }
}
