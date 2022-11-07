<?php

namespace Tests\Controller\WebApp\Assistance;

use DBAL\SectorEnum;
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
use DBAL\ModalityTypeEnum;
use Enum\ProductCategoryType;
use DBAL\SubSectorEnum;
use Entity\Project;
use Tests\BMSServiceTestCase;

class SelectionCriteriaTest extends BMSServiceTestCase
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::getContainer()->get('test.client');
        $this->location = $this->em->getRepository(Location::class)->findOneBy(['code' => self::LOCATION_CODE]);
    }

    private $location;

    private const LOCATION_CODE = 'KH01';

    /**
     * @param $criteria array[] will be in distinct groups
     */
    private function assistanceWithCriteria($criteria): array
    {
        return [
            'iso3' => 'KHM',
            'projectId' => 8,
            'locationId' => 30,
            'dateDistribution' => '2021-03-10T13:45:32.988Z',
            'sector' => SectorEnum::FOOD_SECURITY,
            'subsector' => SubSectorEnum::FOOD_CASH_FOR_WORK,
            'scoringType' => 'Default',
            'type' => AssistanceType::DISTRIBUTION,
            'target' => AssistanceTargetType::HOUSEHOLD,
            'threshold' => null,
            'commodities' => [
                ['modalityType' => ModalityType::SMART_CARD, 'unit' => 'USD', 'value' => 4000],
                ['modalityType' => ModalityType::CASH, 'unit' => 'CZK', 'value' => 100],
                ['modalityType' => ModalityType::CASH, 'unit' => 'CZK', 'value' => 200],
                ['modalityType' => ModalityType::CASH, 'unit' => 'USD', 'value' => 400],
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
        $group = 0;
        $bornBefore2020 = [
            'group' => $group++,
            'target' => SelectionCriteriaTarget::BENEFICIARY,
            'field' => 'dateOfBirth',
            'condition' => '<',
            'weight' => 1,
            'value' => '2020-01-01',
        ];
        $femaleHead = [
            'group' => $group++,
            'target' => SelectionCriteriaTarget::HOUSEHOLD_HEAD,
            'field' => 'gender',
            'condition' => '=',
            'weight' => 1,
            'value' => 'F',
        ];
        $femaleHeadLongString = [
            'group' => $group++,
            'target' => SelectionCriteriaTarget::HOUSEHOLD_HEAD,
            'field' => 'gender',
            'condition' => '=',
            'weight' => 1,
            'value' => 'female',
        ];
        $hasAnyIncomeString = [
            'group' => $group++,
            'target' => SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'income',
            'condition' => '>',
            'weight' => 1,
            'value' => '0',
        ];
        $hasAnyIncomeInt = [
            'group' => $group++,
            'target' => SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'income',
            'condition' => '>',
            'weight' => 1,
            'value' => 0,
        ];
        $location = [
            'group' => $group++,
            'target' => SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'location',
            'condition' => '=',
            'weight' => 1,
            'value' => $this->location->getId(),
        ];
        $CSOEquityCard = [
            'group' => $group++,
            'target' => SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'equityCardNo',
            'condition' => '=',
            'weight' => 1,
            'value' => '111222333',
        ];
        $CSOFloatGtInt = [
            'group' => $group++,
            'target' => SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'CSO float property',
            'condition' => '>',
            'weight' => 1,
            'value' => 0,
        ];
        $CSOFloatLtFloat = [
            'group' => $group++,
            'target' => SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'CSO float property',
            'condition' => '<',
            'weight' => 1,
            'value' => 1.00000001,
        ];
        $CSOFloatGteFloat = [
            'group' => $group++,
            'target' => SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'CSO float property',
            'condition' => '>=',
            'weight' => 1,
            'value' => 0.5,
        ];
        $workForGovernment = [
            'group' => $group++,
            'target' => SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'livelihood',
            'condition' => '=',
            'weight' => 1,
            'value' => 'Regular salary - public sector',
        ];
        yield 'female head' => [$this->assistanceWithCriteria([$femaleHead])];
        yield 'female head (long string)' => [$this->assistanceWithCriteria([$femaleHeadLongString])];
        yield 'born before 2020' => [$this->assistanceWithCriteria([$bornBefore2020])];
        yield 'has any income (string value)' => [$this->assistanceWithCriteria([$hasAnyIncomeString])];
        yield 'has any income (int value)' => [$this->assistanceWithCriteria([$hasAnyIncomeInt])];
        yield 'is in location Banteay Meanchey' => [$this->assistanceWithCriteria([$location])];
        yield 'CSO equity card exact string value' => [$this->assistanceWithCriteria([$CSOEquityCard])];
        yield 'CSO float property higher integer value' => [$this->assistanceWithCriteria([$CSOFloatGtInt])];
        yield 'CSO float property lower float value' => [$this->assistanceWithCriteria([$CSOFloatLtFloat])];
        yield 'CSO float property greater or equal float value' => [$this->assistanceWithCriteria([$CSOFloatGteFloat])];
        yield 'Livelihood for government' => [$this->assistanceWithCriteria([$workForGovernment])];
        yield 'all in one' => [
            $this->assistanceWithCriteria(
                [
                    $location,
                    $femaleHead,
                    $bornBefore2020,
                    $hasAnyIncomeInt,
                    $CSOEquityCard,
                    $CSOFloatGtInt,
                    $CSOFloatLtFloat,
                    $CSOFloatGteFloat,
                    $workForGovernment,
                ]
            ),
        ];
    }

    /**
     * @dataProvider assistanceArrayGenerator
     */
    public function testCommodityCountOfCreatedAssistance(array $assistanceArray)
    {
        $this->request('POST', '/api/basic/web-app/v1/assistances/commodities', $assistanceArray);

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
     * @dataProvider assistanceArrayGenerator
     */
    public function testBeneficiaryPrecalculations(array $assistanceArray)
    {
        $this->request('POST', '/api/basic/web-app/v1/assistances/beneficiaries', $assistanceArray);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $contentArray = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertGreaterThan(0, $contentArray['totalCount']);
    }

    /**
     * @dataProvider assistanceArrayGenerator
     */
    public function testVulnerabilityPrecalculations(array $assistanceArray)
    {
        $this->request('POST', '/api/basic/web-app/v1/assistances/vulnerability-scores', $assistanceArray);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
    }

    /**
     * @dataProvider assistanceArrayGenerator
     */
    public function testCreateAssistance(array $assistanceArray)
    {
        $this->request('POST', '/api/basic/web-app/v1/assistances', $assistanceArray);

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
            "householdsTargeted": "*",
            "individualsTargeted": "*",
            "description": "*",
            "commodityIds": ["*"],
            "foodLimit": 10.99,
            "nonFoodLimit": null,
            "cashbackLimit": 1024,
            "allowedProductCategoryTypes": ["*"],
            "remoteDistributionAllowed": "*"
        }',
            $this->client->getResponse()->getContent()
        );
    }
}
