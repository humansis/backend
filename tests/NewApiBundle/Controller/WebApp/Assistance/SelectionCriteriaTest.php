<?php

namespace Tests\NewApiBundle\Controller\WebApp\Assistance;

use BeneficiaryBundle\Entity\Community;
use CommonBundle\Entity\Location;
use DateTime;
use DateTimeInterface;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\ModalityType;
use DistributionBundle\Enum\AssistanceType;
use DistributionBundle\Repository\AssistanceRepository;
use DistributionBundle\Repository\ModalityTypeRepository;
use Exception;
use NewApiBundle\Component\Assistance\Enum\CommodityDivision;
use NewApiBundle\DBAL\ModalityTypeEnum;
use NewApiBundle\Enum\ProductCategoryType;
use ProjectBundle\DBAL\SubSectorEnum;
use ProjectBundle\Entity\Project;
use Tests\BMSServiceTestCase;

class SelectionCriteriaTest extends BMSServiceTestCase
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

    /**
     * @param $criteria array[] will be in distinct groups
     *
     * @return array
     */
    private function assistanceWithCriteria($criteria): array
    {
        $group = 0;
        foreach ($criteria as $criterion) {
            $criterion['group'] = $group++;
        }
        return [
            'iso3' => 'KHM',
            'projectId' => 8,
            'locationId' => 30,
            'dateDistribution' => '2021-03-10T13:45:32.988Z',
            'sector' => \ProjectBundle\DBAL\SectorEnum::FOOD_SECURITY,
            'subsector' => \ProjectBundle\DBAL\SubSectorEnum::FOOD_CASH_FOR_WORK,
            'type' => AssistanceType::DISTRIBUTION,
            'target' => \DistributionBundle\Enum\AssistanceTargetType::HOUSEHOLD,
            'threshold' => 1,
            'commodities' => [
                ['modalityType' => \NewApiBundle\Enum\ModalityType::SMART_CARD, 'unit' => 'CZK', 'value' => 1000],
                ['modalityType' => \NewApiBundle\Enum\ModalityType::SMART_CARD, 'unit' => 'CZK', 'value' => 2000],
                ['modalityType' => \NewApiBundle\Enum\ModalityType::SMART_CARD, 'unit' => 'USD', 'value' => 4000],
                ['modalityType' => \NewApiBundle\Enum\ModalityType::CASH, 'unit' => 'CZK', 'value' => 100],
                ['modalityType' => \NewApiBundle\Enum\ModalityType::CASH, 'unit' => 'CZK', 'value' => 200],
                ['modalityType' => \NewApiBundle\Enum\ModalityType::CASH, 'unit' => 'USD', 'value' => 400],
            ],
            'selectionCriteria' => $criteria,
            'foodLimit' => 10.99,
            'nonFoodLimit' => null,
            'cashbackLimit' => 1024,
            'remoteDistributionAllowed' => false,
            'allowedProductCategoryTypes' => [ProductCategoryType::CASHBACK, ProductCategoryType::NONFOOD],
        ];
    }

    public function assistanceArrayGenerator(): iterable
    {
        $bornBefore2020 = [
            'group' => 1,
            'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::BENEFICIARY,
            'field' => 'dateOfBirth',
            'condition' => '<',
            'weight' => 1,
            'value' => '2020-01-01',
        ];
        $femaleHead = [
            'group' => 1,
            'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::HOUSEHOLD_HEAD,
            'field' => 'gender',
            'condition' => '=',
            'weight' => 1,
            'value' => 'F',
        ];
        $femaleHeadLongString = [
            'group' => 1,
            'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::HOUSEHOLD_HEAD,
            'field' => 'gender',
            'condition' => '=',
            'weight' => 1,
            'value' => 'female',
        ];
        $hasAnyIncomeString = [
            'group' => 1,
            'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'incomeLevel',
            'condition' => '>',
            'weight' => 1,
            'value' => '0',
        ];
        $hasAnyIncomeInt = [
            'group' => 1,
            'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'incomeLevel',
            'condition' => '>',
            'weight' => 1,
            'value' => 0,
        ];
        $locationString = [
            'group' => 1,
            'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'location',
            'condition' => '=',
            'weight' => 1,
            'value' => '21',
        ];
        $locationInt = [
            'group' => 1,
            'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'location',
            'condition' => '=',
            'weight' => 1,
            'value' => 21,
        ];
        $CSOEquityCard = [
            'group' => 1,
            'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'equityCardNo',
            'condition' => '=',
            'weight' => 1,
            'value' => '111222333',
        ];
        $workForGovernment = [
            'group' => 1,
            'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'livelihood',
            'condition' => '=',
            'weight' => 1,
            'value' => 'Government',
        ];
        yield 'female head' => [$this->assistanceWithCriteria([$femaleHead])];
        yield 'female head (long string)' => [$this->assistanceWithCriteria([$femaleHeadLongString])];
        yield 'born before 2020' => [$this->assistanceWithCriteria([$bornBefore2020])];
        yield 'has any income (string value)' => [$this->assistanceWithCriteria([$hasAnyIncomeString])];
        yield 'has any income (int value)' => [$this->assistanceWithCriteria([$hasAnyIncomeInt])];
        yield 'is in location Banteay Meanchey (string value)' => [$this->assistanceWithCriteria([$locationString])];
        yield 'is in location Banteay Meanchey (int value)' => [$this->assistanceWithCriteria([$locationInt])];
        yield 'CSO equity card' => [$this->assistanceWithCriteria([$CSOEquityCard])];
        yield 'Livelihood for government' => [$this->assistanceWithCriteria([$workForGovernment])];
        yield 'all in one' => [$this->assistanceWithCriteria([$femaleHead, $bornBefore2020, $hasAnyIncomeInt, $locationInt, $CSOEquityCard, $workForGovernment])];
    }

    /**
     * @dataProvider assistanceArrayGenerator
     */
    public function testCommodityCountOfCreatedAssistance(array $assistanceArray)
    {
        $this->request('POST', '/api/basic/web-app/v1/assistances/commodities', $assistanceArray);

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
     * @dataProvider assistanceArrayGenerator
     */
    public function testBeneficiaryPrecalculations(array $assistanceArray)
    {
        $this->request('POST', '/api/basic/web-app/v1/assistances/beneficiaries', $assistanceArray);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
    }

    /**
     * @dataProvider assistanceArrayGenerator
     */
    public function testVulnerabilityPrecalculations(array $assistanceArray)
    {
        $this->request('POST', '/api/basic/web-app/v1/assistances/vulnerability-scores', $assistanceArray);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
    }

    /**
     * @dataProvider assistanceArrayGenerator
     */
    public function testCreateAssistance(array $assistanceArray)
    {
        $commodity = [
            'modalityType' => \NewApiBundle\Enum\ModalityType::PAPER_VOUCHER,
            'unit' => 'CZK',
            'value' => '1000',
            'description' => 'something important',
            "remoteDistributionAllowed" => false,
            'division' => CommodityDivision::PER_HOUSEHOLD_MEMBER,
        ];

        /** @var Project $project */
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findOneBy([], ['id' => 'asc']);

        /** @var Location $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findOneBy([], ['id' => 'asc']);

        if (null === $project || null === $location) {
            $this->markTestSkipped('There needs to be at least one project and location in system for completing this test');
        }

        $this->request('POST', '/api/basic/web-app/v1/assistances', $assistanceArray);
        // $this->request('POST', '/api/basic/web-app/v1/assistances', [
        //     'iso3' => 'KHM',
        //     'projectId' => $project->getId(),
        //     'locationId' => $location->getId(),
        //     'dateDistribution' => '2021-03-10T13:45:32.988Z',
        //     'sector' => \ProjectBundle\DBAL\SectorEnum::FOOD_SECURITY,
        //     'subsector' => \ProjectBundle\DBAL\SubSectorEnum::FOOD_CASH_FOR_WORK,
        //     'type' => AssistanceType::DISTRIBUTION,
        //     'target' => \DistributionBundle\Enum\AssistanceTargetType::HOUSEHOLD,
        //     'threshold' => 1,
        //     'commodities' => [$commodity],
        //     'selectionCriteria' => $selectionCriteria,
        //     'foodLimit' => 10.99,
        //     'nonFoodLimit' => null,
        //     'cashbackLimit' => 1024,
        //     'remoteDistributionAllowed' => $commodity['modalityType']==\NewApiBundle\Enum\ModalityType::SMART_CARD ? false : null,
        //     'allowedProductCategoryTypes' => [ProductCategoryType::CASHBACK, ProductCategoryType::NONFOOD],
        // ]);

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
    }

}
