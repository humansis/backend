<?php

namespace Tests\Controller;

use DBAL\SectorEnum;
use Doctrine\Common\Collections\Criteria;
use Entity\Commodity;
use Entity\Community;
use Entity\Location;
use DateTime;
use DateTimeInterface;
use Entity\Assistance;
use Enum\AssistanceTargetType;
use Enum\AssistanceType;
use Enum\ModalityType;
use Enum\SelectionCriteriaTarget;
use Repository\AssistanceRepository;
use Exception;
use Component\Assistance\Enum\CommodityDivision;
use Enum\ProductCategoryType;
use DBAL\SubSectorEnum;
use Entity\Project;
use Tests\BMSServiceTestCase;

class AssistanceControllerTest extends BMSServiceTestCase
{
    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::getContainer()->get('test.client');
    }

    public function testGetItem()
    {
        /** @var Assistance $assistance */
        $assistance = self::getContainer()->get('doctrine')->getRepository(Assistance::class)->findBy([], ['id' => 'asc'])[0];
        $commodityIds = array_map(fn(Commodity $commodity) => $commodity->getId(), $assistance->getCommodities()->toArray());

        $this->request('GET', '/api/basic/web-app/v1/assistances/' . $assistance->getId());

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "id": ' . $assistance->getId() . ',
            "name": "' . $assistance->getName() . '",
            "dateDistribution": "' . $assistance->getDateDistribution()->format(DateTime::ATOM) . '",
            "dateExpiration": "*",
            "projectId": ' . $assistance->getProject()->getId() . ',
            "location": {
                "id": ' . $assistance->getLocation()->getId() . ',
                "name": "' . $assistance->getLocation()->getName() . '",
                "code": "' . $assistance->getLocation()->getCode() . '",
                "locationId": ' . $assistance->getLocation()->getId() . '
            },
            "target": "' . $assistance->getTargetType() . '",
            "type": "' . $assistance->getAssistanceType() . '",
            "sector": "' . $assistance->getSector() . '",
            "subsector": "*",
            "scoringBlueprint": "*",
            "householdsTargeted": ' . ($assistance->getHouseholdsTargeted() ?: 'null') . ',
            "individualsTargeted": ' . ($assistance->getIndividualsTargeted() ?: 'null') . ',
            "description": "*",
            "commodities": ["*"],
            "validated": ' . ($assistance->isValidated() ? 'true' : 'false') . ',
            "completed": ' . ($assistance->getCompleted() ? 'true' : 'false') . ',
            "foodLimit": "*",
            "nonFoodLimit": "*",
            "cashbackLimit": "*",
            "allowedProductCategoryTypes": "*",
            "threshold": ' . ($assistance->getAssistanceSelection()->getThreshold() ?: 'null') . ',
            "deletable": ' . ($assistance->isValidated() ? 'false' : 'true') . '
        }',
            $this->client->getResponse()->getContent()
        );
    }

    public function testList()
    {
        /** @var Project $project */
        $project = self::getContainer()->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];
        /** @var Location $location */
        $location = self::getContainer()->get('doctrine')->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];

        $this->request(
            'GET',
            '/api/basic/web-app/v1/assistances?filter[type]=' . AssistanceType::DISTRIBUTION .
            '&filter[modalityTypes][]=Smartcard' .
            '&filter[projects][]=' . $project->getId() .
            '&filter[locations][]=' . $location->getId()
        );

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testAsisstancesByProject()
    {
        $project = self::getContainer()->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        $this->request('GET', '/api/basic/web-app/v1/projects/' . $project->getId() . '/assistances');

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function commodityGenerator(): iterable
    {
        yield ModalityType::SMART_CARD => [
            [
                'commodity' => [
                    'modalityType' => ModalityType::SMART_CARD,
                    'unit' => 'CZK',
                    'value' => 1000,
                    'division' => [
                        'code' => CommodityDivision::PER_HOUSEHOLD_MEMBER,
                        'quantities' => null,
                    ],
                ],
                'response' => true,
            ],
        ];
        yield ModalityType::PAPER_VOUCHER => [
            [
                'commodity' => [
                    'modalityType' => ModalityType::PAPER_VOUCHER,
                    'unit' => 'CZK',
                    'value' => '1000',
                    'description' => 'something important',
                    "remoteDistributionAllowed" => false,
                    'division' => [
                        'code' => CommodityDivision::PER_HOUSEHOLD_MEMBER,
                        'quantities' => null,
                    ],
                ],
                'response' => true,
            ],
        ];
        yield ModalityType::QR_CODE_VOUCHER => [
            [
                'commodity' => [
                    'modalityType' => ModalityType::QR_CODE_VOUCHER,
                    'unit' => 'CZK',
                    'value' => "1000.00",
                    'description' => '',
                    "remoteDistributionAllowed" => false,
                    'division' => [
                        'code' => CommodityDivision::PER_HOUSEHOLD_MEMBER,
                        'quantities' => null,
                    ],
                ],
                'response' => true,
            ],
        ];
        yield ModalityType::MOBILE_MONEY => [
            [
                'commodity' => [
                    'modalityType' => ModalityType::MOBILE_MONEY,
                    'unit' => 'CZK',
                    'value' => '0.00',
                    'division' => [
                        'code' => CommodityDivision::PER_HOUSEHOLD_MEMBER,
                        'quantities' => null,
                    ],
                ],
                'response' => true,
            ],
        ];
        yield ModalityType::BREAD => [
            [
                'commodity' => [
                    'modalityType' => ModalityType::BREAD,
                    'unit' => 'ks',
                    'value' => 1,
                    'division' => [
                        'code' => CommodityDivision::PER_HOUSEHOLD_MEMBER,
                        'quantities' => null,
                    ],
                ],
                'response' => true,
            ],
        ];
        yield 'Smartcard for household' => [
            [
                'commodity' => [
                    'modalityType' => ModalityType::SMART_CARD,
                    'unit' => 'CZK',
                    'value' => 1000,
                    'division' => [
                        'code' => CommodityDivision::PER_HOUSEHOLD,
                        'quantities' => null,
                    ],
                ],
                'response' => true,
            ],
        ];
        yield 'No quantities for members' => [
            [
                'commodity' => [
                    'modalityType' => ModalityType::SMART_CARD,
                    'unit' => 'CZK',
                    'value' => 1000,
                    'division' => [
                        'code' => CommodityDivision::PER_HOUSEHOLD_MEMBERS,
                        'quantities' => null,
                    ],
                ],
                'response' => false,
            ],
        ];
        yield 'Empty quantities for members' => [
            [
                'commodity' => [
                    'modalityType' => ModalityType::SMART_CARD,
                    'unit' => 'CZK',
                    'value' => 1000,
                    'division' => [
                        'code' => CommodityDivision::PER_HOUSEHOLD_MEMBERS,
                        'quantities' => [],
                    ],
                ],
                'response' => false,
            ],
        ];
        yield 'Quantities for member' => [
            [
                'commodity' => [
                    'modalityType' => ModalityType::SMART_CARD,
                    'unit' => 'CZK',
                    'value' => 1000,
                    'division' => [
                        'code' => CommodityDivision::PER_HOUSEHOLD_MEMBER,
                        'quantities' => [
                            [
                                'rangeFrom' => 1,
                                'rangeTo' => null,
                                'value' => 1000,
                            ],
                        ],
                    ],
                ],
                'response' => false,
            ],
        ];
        yield 'Correct quantities for members' => [
            [
                'commodity' => [
                    'modalityType' => ModalityType::SMART_CARD,
                    'unit' => 'CZK',
                    'value' => 1000,
                    'division' => [
                        'code' => CommodityDivision::PER_HOUSEHOLD_MEMBERS,
                        'quantities' => [
                            [
                                'rangeFrom' => 1,
                                'rangeTo' => 5,
                                'value' => 100,
                            ],
                            [
                                'rangeFrom' => 6,
                                'rangeTo' => 8,
                                'value' => 120,
                            ],
                            [
                                'rangeFrom' => 9,
                                'rangeTo' => null,
                                'value' => 150,
                            ],
                        ],
                    ],
                ],
                'response' => true,
            ],
        ];
        yield 'Not correct quantities for members - missing range from 1' => [
            [
                'commodity' => [
                    'modalityType' => ModalityType::SMART_CARD,
                    'unit' => 'CZK',
                    'value' => 1000,
                    'division' => [
                        'code' => CommodityDivision::PER_HOUSEHOLD_MEMBERS,
                        'quantities' => [
                            [
                                'rangeFrom' => 2,
                                'rangeTo' => 5,
                                'value' => 100,
                            ],
                            [
                                'rangeFrom' => 6,
                                'rangeTo' => 8,
                                'value' => 120,
                            ],
                            [
                                'rangeFrom' => 9,
                                'rangeTo' => null,
                                'value' => 150,
                            ],
                        ],
                    ],
                ],
                'response' => false,
            ],
        ];
        yield 'Not correct quantities for members - missing range to null' => [
            [
                'commodity' => [
                    'modalityType' => ModalityType::SMART_CARD,
                    'unit' => 'CZK',
                    'value' => 1000,
                    'division' => [
                        'code' => CommodityDivision::PER_HOUSEHOLD_MEMBERS,
                        'quantities' => [
                            [
                                'rangeFrom' => 1,
                                'rangeTo' => 5,
                                'value' => 100,
                            ],
                            [
                                'rangeFrom' => 6,
                                'rangeTo' => 8,
                                'value' => 120,
                            ],
                            [
                                'rangeFrom' => 9,
                                'rangeTo' => 10,
                                'value' => 150,
                            ],
                        ],
                    ],
                ],
                'response' => false,
            ],
        ];
        yield 'Not correct quantities for members - not following up ranges' => [
            [
                'commodity' => [
                    'modalityType' => ModalityType::SMART_CARD,
                    'unit' => 'CZK',
                    'value' => 1000,
                    'division' => [
                        'code' => CommodityDivision::PER_HOUSEHOLD_MEMBERS,
                        'quantities' => [
                            [
                                'rangeFrom' => 1,
                                'rangeTo' => 5,
                                'value' => 100,
                            ],
                            [
                                'rangeFrom' => 7,
                                'rangeTo' => 8,
                                'value' => 120,
                            ],
                            [
                                'rangeFrom' => 9,
                                'rangeTo' => null,
                                'value' => 150,
                            ],
                        ],
                    ],
                ],
                'response' => false,
            ],
        ];
    }

    /**
     * @dataProvider commodityGenerator
     */
    public function testCreateDistribution(array $commodity)
    {
        /** @var Project $project */
        $project = self::getContainer()->get('doctrine')->getRepository(Project::class)->findOneBy([], ['id' => 'asc']);

        /** @var Location $location */
        $location = self::getContainer()->get('doctrine')->getRepository(Location::class)->findOneBy([], ['id' => 'asc']);

        if (null === $project || null === $location) {
            $this->markTestSkipped(
                'There needs to be at least one project and location in system for completing this test'
            );
        }

        $this->request('POST', '/api/basic/web-app/v1/assistances', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2021-03-10T13:45:32.988Z',
            'sector' => SectorEnum::FOOD_SECURITY,
            'subsector' => SubSectorEnum::FOOD_CASH_FOR_WORK,
            'scoringBlueprint' => null,
            'type' => AssistanceType::DISTRIBUTION,
            'target' => AssistanceTargetType::HOUSEHOLD,
            'threshold' => null,
            'commodities' => [$commodity['commodity']],
            'selectionCriteria' => [
                [
                    'group' => 1,
                    'target' => SelectionCriteriaTarget::BENEFICIARY,
                    'field' => 'dateOfBirth',
                    'condition' => '<',
                    'weight' => 1,
                    'value' => '2020-01-01',
                ],
            ],
            'foodLimit' => 10.99,
            'nonFoodLimit' => null,
            'cashbackLimit' => 1024,
            'remoteDistributionAllowed' => $commodity['commodity']['modalityType'] === ModalityType::SMART_CARD ? false : null,
            'allowedProductCategoryTypes' => [ProductCategoryType::CASHBACK, ProductCategoryType::NONFOOD],
        ]);

        if ($commodity['response']) {
            $this->assertTrue(
                $this->client->getResponse()->isSuccessful(),
                'Request failed: ' . $this->client->getResponse()->getContent()
            );
            $this->assertJsonFragment(
                '{
            "id": "*",
            "name": "*",
            "dateDistribution": "*",
            "dateExpiration": null,
            "projectId": "*",
            "location": "*",
            "target": "*",
            "type": "*",
            "sector": "*",
            "subsector": "*",
            "scoringBlueprint": "*",
            "householdsTargeted": "*",
            "individualsTargeted": "*",
            "description": "*",
            "commodities": ["*"],
            "foodLimit": 10.99,
            "nonFoodLimit": null,
            "cashbackLimit": 1024,
            "allowedProductCategoryTypes": ["*"],
            "remoteDistributionAllowed": "*"
        }',
                $this->client->getResponse()->getContent()
            );

            $contentArray = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

            return $contentArray['id'];
        } else {
            $this->assertTrue(
                $this->client->getResponse()->isClientError(),
                'Request should return client error. ' . $this->client->getResponse()->getContent() . ' given'
            );

            return null;
        }
    }

    public function testCommodityCountOfCreatedAssistance()
    {
        /** @var Project $project */
        $project = self::getContainer()->get('doctrine')->getRepository(Project::class)->findOneBy([], ['id' => 'asc']);

        /** @var Location $location */
        $location = self::getContainer()->get('doctrine')->getRepository(Location::class)->findOneBy([], ['id' => 'asc']);

        if (null === $project || null === $location) {
            $this->markTestSkipped(
                'There needs to be at least one project and location in system for completing this test'
            );
        }

        $smartcardModalityType = ModalityType::SMART_CARD;
        $cashModalityType = ModalityType::CASH;

        $this->request('POST', '/api/basic/web-app/v1/assistances/commodities', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2021-03-10T13:45:32.988Z',
            'sector' => SectorEnum::FOOD_SECURITY,
            'subsector' => SubSectorEnum::FOOD_CASH_FOR_WORK,
            'scoringBlueprint' => null,
            'type' => AssistanceType::DISTRIBUTION,
            'target' => AssistanceTargetType::HOUSEHOLD,
            'threshold' => null,
            'commodities' => [
                ['modalityType' => $smartcardModalityType, 'unit' => 'USD', 'value' => 4000],
                ['modalityType' => $cashModalityType, 'unit' => 'CZK', 'value' => 100],
                ['modalityType' => $cashModalityType, 'unit' => 'CZK', 'value' => 200],
                ['modalityType' => $cashModalityType, 'unit' => 'USD', 'value' => 400],
            ],
            'selectionCriteria' => [
                [
                    'group' => 1,
                    'target' => SelectionCriteriaTarget::BENEFICIARY,
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
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment(
            '{
            "totalCount": 3,
            "data": [
                {
                "modalityType": "*",
                "unit": "*",
                "value": "*"
                }
             ]
        }',
            $this->client->getResponse()->getContent(),
        );
        $contentArray = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        foreach ($contentArray['data'] as $summary) {
            $this->assertTrue(in_array($summary['modalityType'], [ModalityType::SMART_CARD, ModalityType::CASH]));
            $this->assertTrue(in_array($summary['unit'], ['CZK', 'USD']));
            $this->assertGreaterThan(0, $summary['value']);
        }
    }

    /**
     * @depends testCreateDistribution
     */
    public function testUpdateDistributionDate()
    {
        $assistance = self::getContainer()->get('doctrine')->getRepository(Assistance::class)->findOneBy([
            'validatedBy' => null,
            'completed' => false,
        ], ['updatedOn' => 'desc']);
        $date = new DateTime();

        $this->request('PATCH', "/api/basic/web-app/v1/assistances/" . $assistance->getId(), [
            'dateDistribution' => $date->format(DateTimeInterface::ATOM),
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $contentArray = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($date->format(DateTimeInterface::ATOM), $contentArray['dateDistribution']);
    }

    /**
     * @depends testCreateDistribution
     */
    public function testUpdateExpirationDate()
    {
        $assistance = self::getContainer()->get('doctrine')->getRepository(Assistance::class)->findOneBy([
            'validatedBy' => null,
            'completed' => false,
        ], ['updatedOn' => 'desc']);
        $date = new DateTime('+1 year');

        $this->request('PATCH', "/api/basic/web-app/v1/assistances/" . $assistance->getId(), [
            'dateExpiration' => $date->format(DateTimeInterface::ATOM),
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $contentArray = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($date->format(DateTimeInterface::ATOM), $contentArray['dateExpiration']);
    }

    public function testCreateDistributionWithExpirationDate()
    {
        /** @var Project $project */
        $project = self::getContainer()->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        /** @var Location $location */
        $location = self::getContainer()->get('doctrine')->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];

        $modalityType = ModalityType::CASH;

        $this->request('POST', '/api/basic/web-app/v1/assistances', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2021-03-10T13:45:32.988Z',
            'dateExpiration' => '2022-10-10T03:45:00.000Z',
            'sector' => SectorEnum::FOOD_SECURITY,
            'subsector' => SubSectorEnum::FOOD_CASH_FOR_WORK,
            'scoringBlueprint' => null,
            'type' => AssistanceType::DISTRIBUTION,
            'target' => AssistanceTargetType::INDIVIDUAL,
            'threshold' => null,
            'commodities' => [
                ['modalityType' => $modalityType,
                    'unit' => 'CZK',
                    'value' => 1000,
                    'division' => null
                ],
            ],
            'selectionCriteria' => [
                [
                    'group' => 1,
                    'target' => SelectionCriteriaTarget::BENEFICIARY,
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
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "id": "*",
            "name": "*",
            "dateDistribution": "2021-03-10T13:45:32+00:00",
            "dateExpiration": "2022-10-10T03:45:00+00:00",
            "projectId": "*",
            "location": "*",
            "target": "*",
            "type": "*",
            "sector": "*",
            "subsector": "*",
            "scoringBlueprint": "*",
            "householdsTargeted": "*",
            "individualsTargeted": "*",
            "deletable": true,
            "description": "*",
            "allowedProductCategoryTypes": [],
            "foodLimit": null,
            "nonFoodLimit": null,
            "cashbackLimit": null,
            "commodities": ["*"]
        }',
            $this->client->getResponse()->getContent()
        );
    }

    public function testCreateActivity()
    {
        /** @var Project $project */
        $project = self::getContainer()->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        /** @var Location $location */
        $location = self::getContainer()->get('doctrine')->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];

        $modalityType = ModalityType::CASH;

        $this->request('POST', '/api/basic/web-app/v1/assistances', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2000-12-01T01:01:01+00:00',
            'sector' => SectorEnum::LIVELIHOODS,
            'subsector' => SubSectorEnum::SKILLS_TRAINING,
            'scoringBlueprint' => null,
            'type' => AssistanceType::ACTIVITY,
            'target' => AssistanceTargetType::INDIVIDUAL,
            'threshold' => null,
            'commodities' => [
                ['modalityType' => $modalityType, 'unit' => 'CZK', 'value' => 1000, 'division' => null],
            ],
            'selectionCriteria' => [
                [
                    'group' => 1,
                    'target' => SelectionCriteriaTarget::BENEFICIARY,
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
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "id": "*",
            "name": "*",
            "dateDistribution": "*",
            "dateExpiration": null,
            "projectId": "*",
            "location": "*",
            "target": "*",
            "type": "*",
            "sector": "*",
            "subsector": "*",
            "scoringBlueprint": "*",
            "householdsTargeted": "*",
            "individualsTargeted": "*",
            "deletable": true,
            "description": "*",
            "commodities": "*"
        }',
            $this->client->getResponse()->getContent()
        );
    }

    public function testCreateCommunityActivity()
    {
        /** @var Project $project */
        $project = self::getContainer()->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        /** @var Location $location */
        $location = self::getContainer()->get('doctrine')->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];

        /** @var Community $community */
        $community = self::getContainer()->get('doctrine')->getRepository(Community::class)->findBy([], ['id' => 'asc'])[0];

        /** @var ModalityType $modalityType */
        $modalityType = ModalityType::CASH;

        $this->request('POST', '/api/basic/web-app/v1/assistances', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2000-12-01T01:01:01+0000',
            'sector' => SectorEnum::SHELTER,
            'subsector' => SubSectorEnum::CONSTRUCTION,
            //'scoringBlueprint' => null,
            'type' => AssistanceType::ACTIVITY,
            'target' => AssistanceTargetType::COMMUNITY,
            'commodities' => [
                ['modalityType' => $modalityType, 'unit' => 'CZK', 'value' => 1000, 'division' => null],
            ],
            'communities' => [$community->getId()],
            'description' => 'test construction activity',
            'householdsTargeted' => 10,
            'individualsTargeted' => null,
            'allowedProductCategoryTypes' => [ProductCategoryType::CASHBACK, ProductCategoryType::NONFOOD],
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "id": "*",
            "name": "*",
            "dateDistribution": "*",
            "dateExpiration": null,
            "projectId": "*",
            "location": "*",
            "target": "*",
            "type": "*",
            "sector": "*",
            "subsector": "*",
            "scoringBlueprint": "*",
            "householdsTargeted": "*",
            "individualsTargeted": "*",
            "deletable": true,
            "description": "*",
            "commodities": []
        }',
            $this->client->getResponse()->getContent()
        );
    }

    public function testCreateRemoteDistributionWithValidSmartcard(): void
    {
        /** @var Project $project */
        $project = self::getContainer()->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        /** @var Location $location */
        $location = self::getContainer()->get('doctrine')->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];

        $modalityType = ModalityType::SMART_CARD;

        $this->request('POST', '/api/basic/web-app/v1/assistances', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2021-03-10T13:45:32.988Z',
            'dateExpiration' => '2022-10-10T03:45:00.000Z',
            'sector' => SectorEnum::FOOD_SECURITY,
            'subsector' => SubSectorEnum::FOOD_CASH_FOR_WORK,
            'scoringBlueprint' => null,
            'type' => AssistanceType::DISTRIBUTION,
            'target' => AssistanceTargetType::INDIVIDUAL,
            'threshold' => null,
            'commodities' => [
                ['modalityType' => $modalityType, 'unit' => 'CZK', 'value' => 1000, 'division' => null],
            ],
            'selectionCriteria' => [
                [
                    'group' => 1,
                    'target' => SelectionCriteriaTarget::HOUSEHOLD_HEAD,
                    'field' => 'hasValidSmartcard',
                    'condition' => '=',
                    'weight' => 1,
                    'value' => true,
                ],
                [
                    'group' => 1,
                    'target' => SelectionCriteriaTarget::BENEFICIARY,
                    'field' => 'gender',
                    'condition' => '=',
                    'weight' => 1,
                    'value' => 'F',
                ],
                [
                    'group' => 2,
                    'target' => SelectionCriteriaTarget::HOUSEHOLD_HEAD,
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
            'remoteDistributionAllowed' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "id": "*",
            "name": "*",
            "dateDistribution": "2021-03-10T13:45:32+00:00",
            "dateExpiration": "2022-10-10T03:45:00+00:00",
            "projectId": "*",
            "location": "*",
            "target": "*",
            "type": "*",
            "sector": "*",
            "subsector": "*",
            "scoringBlueprint": "*",
            "householdsTargeted": "*",
            "individualsTargeted": "*",
            "deletable": true,
            "description": "*",
            "allowedProductCategoryTypes": [],
            "foodLimit": null,
            "nonFoodLimit": null,
            "cashbackLimit": null,
            "commodities": ["*"]
        }',
            $this->client->getResponse()->getContent()
        );
    }

    public function testCreateRemoteDistributionWithInvalidSmartcard(): void
    {
        /** @var Project $project */
        $project = self::getContainer()->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        /** @var Location $location */
        $location = self::getContainer()->get('doctrine')->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];

        $modalityType = ModalityType::SMART_CARD;

        $this->request('POST', '/api/basic/web-app/v1/assistances', [
            'iso3' => 'KHM',
            'projectId' => $project->getId(),
            'locationId' => $location->getId(),
            'dateDistribution' => '2021-03-10T13:45:32.988Z',
            'dateExpiration' => '2022-10-10T03:45:00.000Z',
            'sector' => SectorEnum::FOOD_SECURITY,
            'subsector' => SubSectorEnum::FOOD_CASH_FOR_WORK,
            'scoringBlueprint' => null,
            'type' => AssistanceType::DISTRIBUTION,
            'target' => AssistanceTargetType::INDIVIDUAL,
            'threshold' => null,
            'commodities' => [
                ['modalityType' => $modalityType, 'unit' => 'CZK', 'value' => 1000, 'division' => null],
            ],
            'selectionCriteria' => [
                [
                    'group' => 1,
                    'target' => SelectionCriteriaTarget::HOUSEHOLD_HEAD,
                    'field' => 'hasValidSmartcard',
                    'condition' => '=',
                    'weight' => 1,
                    'value' => false,
                ],
                [
                    'group' => 1,
                    'target' => SelectionCriteriaTarget::BENEFICIARY,
                    'field' => 'gender',
                    'condition' => '=',
                    'weight' => 1,
                    'value' => 'F',
                ],
                [
                    'group' => 2,
                    'target' => SelectionCriteriaTarget::HOUSEHOLD_HEAD,
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
            'remoteDistributionAllowed' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->getStatusCode() === \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST,
            'Request should fail because for remote distribution should be only valid smartcard'
        );
    }

    public function testBankReportExportsSuccess()
    {
        /** @var AssistanceRepository $assistanceRepository */
        $assistanceRepository = self::getContainer()->get('doctrine')->getRepository(Assistance::class);

        $commodityData = [
            'value' => 1,
            'unit' => 'USD',
            'modality_type' => ModalityType::CASH,
            'description' => 'Note',
        ];
        /** @var Assistance $assistance */
        $assistance = $assistanceRepository->matching(
            Criteria::create()->where(Criteria::expr()->neq('validatedBy', null))
        )->first();
        $assistance->setAssistanceType(AssistanceType::DISTRIBUTION);
        $assistance->setSubSector(SubSectorEnum::MULTI_PURPOSE_CASH_ASSISTANCE);
        $assistance->addCommodity($this->commodityService->create($assistance, $commodityData, false));
        $id = $assistance->getId();

        $this->request('GET', "/api/basic/web-app/v1/assistances/$id/bank-report/exports", [
            'type' => 'csv',
        ]);
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
    }

    public function testBankReportExportsNotValidated()
    {
        /** @var AssistanceRepository $assistanceRepository */
        $assistanceRepository = self::getContainer()->get('doctrine')->getRepository(Assistance::class);

        $assistance = $assistanceRepository->findOneBy(['validatedBy' => null]);
        $id = $assistance->getId();

        $this->request('GET', "/api/basic/web-app/v1/assistances/$id/bank-report/exports", [
            'type' => 'csv',
        ]);
        $this->assertFalse(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
    }
}
