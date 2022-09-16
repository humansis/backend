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
    /**
     * @throws Exception
     */
    public function setUp(): void
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
            'threshold' => 1,
            'commodities' => [
                ['modalityType' => ModalityType::SMART_CARD, 'unit' => 'CZK', 'value' => 1000],
                ['modalityType' => ModalityType::SMART_CARD, 'unit' => 'CZK', 'value' => 2000],
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
            'target' => \Enum\SelectionCriteriaTarget::BENEFICIARY,
            'field' => 'dateOfBirth',
            'condition' => '<',
            'weight' => 1,
            'value' => '2020-01-01',
        ];
        $femaleHead = [
            'group' => $group++,
            'target' => \Enum\SelectionCriteriaTarget::HOUSEHOLD_HEAD,
            'field' => 'gender',
            'condition' => '=',
            'weight' => 1,
            'value' => 'F',
        ];
        $femaleHeadLongString = [
            'group' => $group++,
            'target' => \Enum\SelectionCriteriaTarget::HOUSEHOLD_HEAD,
            'field' => 'gender',
            'condition' => '=',
            'weight' => 1,
            'value' => 'female',
        ];
        $hasAnyIncomeString = [
            'group' => $group++,
            'target' => \Enum\SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'income',
            'condition' => '>',
            'weight' => 1,
            'value' => '0',
        ];
        $hasAnyIncomeInt = [
            'group' => $group++,
            'target' => \Enum\SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'income',
            'condition' => '>',
            'weight' => 1,
            'value' => 0,
        ];
        $location = [
            'group' => $group++,
            'target' => \Enum\SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'location',
            'condition' => '=',
            'weight' => 1,
            'value' => 21,
        ];
        $CSOEquityCard = [
            'group' => $group++,
            'target' => \Enum\SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'equityCardNo',
            'condition' => '=',
            'weight' => 1,
            'value' => '111222333',
        ];
        $CSOFloatGtInt = [
            'group' => $group++,
            'target' => \Enum\SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'CSO float property',
            'condition' => '>',
            'weight' => 1,
            'value' => 0,
        ];
        $CSOFloatLtFloat = [
            'group' => $group++,
            'target' => \Enum\SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'CSO float property',
            'condition' => '<',
            'weight' => 1,
            'value' => 1.00000001,
        ];
        $CSOFloatGteFloat = [
            'group' => $group++,
            'target' => \Enum\SelectionCriteriaTarget::HOUSEHOLD,
            'field' => 'CSO float property',
            'condition' => '>=',
            'weight' => 1,
            'value' => 0.5,
        ];
        $workForGovernment = [
            'group' => $group++,
            'target' => \Enum\SelectionCriteriaTarget::HOUSEHOLD,
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
        yield 'all in one' => [$this->assistanceWithCriteria([$femaleHead, $bornBefore2020, $hasAnyIncomeInt, $location, $CSOEquityCard, $CSOFloatGtInt, $CSOFloatLtFloat, $CSOFloatGteFloat, $workForGovernment])];
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
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $contentArray = json_decode($this->client->getResponse()->getContent(), true);
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
            'Request failed: '.$this->client->getResponse()->getContent()
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
