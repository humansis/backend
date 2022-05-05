<?php

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Community;
use CommonBundle\Entity\Location;
use DateTime;
use DateTimeInterface;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\ModalityType;
use DistributionBundle\Enum\AssistanceType;
use Exception;
use NewApiBundle\Component\Assistance\Enum\CommodityDivision;
use NewApiBundle\Enum\BeneficiaryType;
use NewApiBundle\Enum\ProductCategoryType;
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
        $assistance = self::$container->get('doctrine')->getRepository(Assistance::class)->findBy([], ['id' => 'asc'])[0];
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
            "foodLimit": "*",
            "nonFoodLimit": "*",
            "cashbackLimit": "*",
            "allowedProductCategoryTypes": "*",
            "deletable": '.($assistance->getValidated() ? 'false' : 'true').'
        }', $this->client->getResponse()->getContent());
    }

    public function testList()
    {
        /** @var Project $project */
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];
        /** @var Location $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];

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
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

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
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findOneBy([], ['id' => 'asc']);

        /** @var Location $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findOneBy([], ['id' => 'asc']);

        if (null === $project || null === $location) {
            $this->markTestSkipped('There needs to be at least one project and location in system for completing this test');
        }

        /** @var ModalityType $modalityType */
        $modalityType = self::$container->get('doctrine')->getRepository(ModalityType::class)->findBy(['name' => 'Smartcard'], ['id' => 'asc'])[0];

        $this->request('POST', '/api/basic/web-app/v1/assistances', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2021-03-10T13:45:32.988Z',
            'sector' => \ProjectBundle\DBAL\SectorEnum::FOOD_SECURITY,
            'subsector' => \ProjectBundle\DBAL\SubSectorEnum::FOOD_CASH_FOR_WORK,
            'type' => AssistanceType::DISTRIBUTION,
            'target' => \DistributionBundle\Enum\AssistanceTargetType::HOUSEHOLD,
            'threshold' => 1,
            'commodities' => [
                [
                    'modalityType' => $modalityType->getName(),
                    'unit' => 'CZK',
                    'value' => 1000,
                    'division' => CommodityDivision::PER_HOUSEHOLD_MEMBER,
                ],
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
            'foodLimit' => 10.99,
            'nonFoodLimit' => null,
            'cashbackLimit' => 1024,
            'remoteDistributionAllowed' => false,
            'allowedProductCategoryTypes' => [ProductCategoryType::CASHBACK, ProductCategoryType::NONFOOD],
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
            "commodityIds": ["*"],
            "foodLimit": 10.99,
            "nonFoodLimit": null,
            "cashbackLimit": 1024,
            "allowedProductCategoryTypes": ["*"],
            "remoteDistributionAllowed": "*"
        }', $this->client->getResponse()->getContent());

        $contentArray = json_decode($this->client->getResponse()->getContent(), true);

        return $contentArray['id'];
    }

    public function testCommodityCountOfCreatedAssistance()
    {
        /** @var Project $project */
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findOneBy([], ['id' => 'asc']);

        /** @var Location $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findOneBy([], ['id' => 'asc']);

        if (null === $project || null === $location) {
            $this->markTestSkipped('There needs to be at least one project and location in system for completing this test');
        }

        /** @var ModalityType $smartcardModalityType */
        $smartcardModalityType = self::$container->get('doctrine')->getRepository(ModalityType::class)->findOneBy(['name' => 'Smartcard'], ['id' => 'asc']);
        $cashModalityType = self::$container->get('doctrine')->getRepository(ModalityType::class)->findOneBy(['name' => 'Cash'], ['id' => 'asc']);

        $this->request('POST', '/api/basic/web-app/v1/assistances/commodities', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2021-03-10T13:45:32.988Z',
            'sector' => \ProjectBundle\DBAL\SectorEnum::FOOD_SECURITY,
            'subsector' => \ProjectBundle\DBAL\SubSectorEnum::FOOD_CASH_FOR_WORK,
            'type' => AssistanceType::DISTRIBUTION,
            'target' => \DistributionBundle\Enum\AssistanceTargetType::HOUSEHOLD,
            'threshold' => 1,
            'commodities' => [
                ['modalityType' => $smartcardModalityType->getName(), 'unit' => 'CZK', 'value' => 1000],
                ['modalityType' => $smartcardModalityType->getName(), 'unit' => 'CZK', 'value' => 2000],
                ['modalityType' => $smartcardModalityType->getName(), 'unit' => 'USD', 'value' => 4000],
                ['modalityType' => $cashModalityType->getName(), 'unit' => 'CZK', 'value' => 100],
                ['modalityType' => $cashModalityType->getName(), 'unit' => 'CZK', 'value' => 200],
                ['modalityType' => $cashModalityType->getName(), 'unit' => 'USD', 'value' => 400],
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
            'foodLimit' => 10.99,
            'nonFoodLimit' => null,
            'cashbackLimit' => 1024,
            'remoteDistributionAllowed' => false,
            'allowedProductCategoryTypes' => [ProductCategoryType::CASHBACK, ProductCategoryType::NONFOOD],
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );


        $this->assertJsonFragment('{
            "totalCount": 4, 
            "data": [
                {
                "modalityType": "*",
                "unit": "*",
                "value": "*"
                }
             ]
        }', $this->client->getResponse()->getContent(),
        );
        $contentArray = json_decode($this->client->getResponse()->getContent(), true);
        foreach ($contentArray['data'] as $summary) {
            $this->assertTrue(in_array($summary['modalityType'], [\NewApiBundle\Enum\ModalityType::SMART_CARD, \NewApiBundle\Enum\ModalityType::CASH]));
            $this->assertTrue(in_array($summary['unit'], ['CZK', 'USD']));
            $this->assertGreaterThan(0, $summary['value']);
        }
    }

    /**
     * @depends testCreateDistribution
     */
    public function testUpdateDistributionDate(int $id)
    {
        $date = new DateTime();

        $this->request('PATCH', "/api/basic/web-app/v1/assistances/$id", [
            'dateDistribution' => $date->format(DateTimeInterface::ISO8601),
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $contentArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($date->format(DateTimeInterface::ISO8601), $contentArray['dateDistribution']);
    }

    /**
     * @depends testCreateDistribution
     */
    public function testUpdateExpirationDate(int $id)
    {
        $date = new DateTime('+1 year');

        $this->request('PATCH', "/api/basic/web-app/v1/assistances/$id", [
            'dateExpiration' => $date->format(DateTimeInterface::ISO8601),
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $contentArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($date->format(DateTimeInterface::ISO8601), $contentArray['dateExpiration']);
    }

    public function testCreateDistributionWithExpirationDate()
    {
        /** @var Project $project */
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        /** @var Location $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];

        /** @var ModalityType $modalityType */
        $modalityType = self::$container->get('doctrine')->getRepository(ModalityType::class)->findBy(['name' => 'Cash'], ['id' => 'asc'])[0];

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
            'foodLimit' => null,
            'nonFoodLimit' => null,
            'cashbackLimit' => null,
            'allowedProductCategoryTypes' => [],
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
            "allowedProductCategoryTypes": [],
            "foodLimit": null,
            "nonFoodLimit": null,
            "cashbackLimit": null,
            "commodityIds": ["*"]
        }', $this->client->getResponse()->getContent());
    }

    public function testCreateActivity()
    {
        /** @var Project $project */
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        /** @var Location $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];

        /** @var ModalityType $modalityType */
        $modalityType = self::$container->get('doctrine')->getRepository(ModalityType::class)->findBy(['name' => 'Cash'], ['id' => 'asc'])[0];

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
            'commodities' => [
                ['modalityType' => $modalityType->getName(), 'unit' => 'CZK', 'value' => 1000],
            ],
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
            'allowedProductCategoryTypes' => [ProductCategoryType::CASHBACK, ProductCategoryType::NONFOOD],
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
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        /** @var Location $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];

        /** @var Community $community */
        $community = self::$container->get('doctrine')->getRepository(Community::class)->findBy([], ['id' => 'asc'])[0];

        /** @var ModalityType $modalityType */
        $modalityType = self::$container->get('doctrine')->getRepository(ModalityType::class)->findBy(['name' => 'Cash'], ['id' => 'asc'])[0];


        $this->request('POST', '/api/basic/web-app/v1/assistances', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2000-12-01T01:01:01+0000',
            'sector' => \ProjectBundle\DBAL\SectorEnum::SHELTER,
            'subsector' => \ProjectBundle\DBAL\SubSectorEnum::CONSTRUCTION,
            'type' => AssistanceType::ACTIVITY,
            'target' => \DistributionBundle\Enum\AssistanceTargetType::COMMUNITY,
            'commodities' => [
                ['modalityType' => $modalityType->getName(), 'unit' => 'CZK', 'value' => 1000],
            ],
            'communities' => [$community->getId()],
            'description' => 'test construction activity',
            'householdsTargeted' => 10,
            'individualsTargeted' => null,
            'allowedProductCategoryTypes' => [ProductCategoryType::CASHBACK, ProductCategoryType::NONFOOD],
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

    public function testCreateRemoteDistributionWithValidSmartcard(): void
    {
        /** @var Project $project */
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        /** @var Location $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];

        /** @var ModalityType $modalityType */
        $modalityType = self::$container->get('doctrine')->getRepository(ModalityType::class)->findBy(['name' => 'Smartcard'], ['id' => 'asc'])[0];

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
                    'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::HOUSEHOLD_HEAD,
                    'field' => 'hasValidSmartcard',
                    'condition' => '=',
                    'weight' => 1,
                    'value' => true,
                ],
                [
                    'group' => 1,
                    'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::BENEFICIARY,
                    'field' => 'gender',
                    'condition' => '=',
                    'weight' => 1,
                    'value' => 'F',
                ],
                [
                    'group' => 2,
                    'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::HOUSEHOLD_HEAD,
                    'field' => 'hasValidSmartcard',
                    'condition' => '=',
                    'weight' => 1,
                    'value' => true,
                ],
            ],
            'foodLimit' => null,
            'nonFoodLimit' => null,
            'cashbackLimit' => null,
            'allowedProductCategoryTypes' => [],
            'remoteDistributionAllowed' => true
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
            "allowedProductCategoryTypes": [],
            "foodLimit": null,
            "nonFoodLimit": null,
            "cashbackLimit": null,
            "commodityIds": ["*"]
        }', $this->client->getResponse()->getContent());
    }

    public function testCreateRemoteDistributionWithInvalidSmartcard(): void
    {
        /** @var Project $project */
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        /** @var Location $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];

        /** @var ModalityType $modalityType */
        $modalityType = self::$container->get('doctrine')->getRepository(ModalityType::class)->findBy(['name' => 'Smartcard'], ['id' => 'asc'])[0];

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
                    'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::HOUSEHOLD_HEAD,
                    'field' => 'hasValidSmartcard',
                    'condition' => '=',
                    'weight' => 1,
                    'value' => false,
                ],
                [
                    'group' => 1,
                    'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::BENEFICIARY,
                    'field' => 'gender',
                    'condition' => '=',
                    'weight' => 1,
                    'value' => 'F',
                ],
                [
                    'group' => 2,
                    'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::HOUSEHOLD_HEAD,
                    'field' => 'hasValidSmartcard',
                    'condition' => '=',
                    'weight' => 1,
                    'value' => true,
                ],
            ],
            'foodLimit' => null,
            'nonFoodLimit' => null,
            'cashbackLimit' => null,
            'allowedProductCategoryTypes' => [],
            'remoteDistributionAllowed' => true
        ]);

        $this->assertTrue(
            $this->client->getResponse()->getStatusCode() === 400,
            'Request should fail because for remote distribution should be only valid smartcard'
        );
    }
}
