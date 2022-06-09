<?php

namespace Tests\NewApiBundle\WebApp\Assistance;

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

    public function criteriaGenerator(): iterable
    {
        $bornBefore2020 = [
            'group' => 1,
            'target' => \NewApiBundle\Enum\SelectionCriteriaTarget::BENEFICIARY,
            'field' => 'dateOfBirth',
            'condition' => '<',
            'weight' => 1,
            'value' => '2020-01-01',
        ];
        yield 'bornBefore' => [
            [$bornBefore2020],
        ];
    }

    /**
     * @dataProvider criteriaGenerator
     */
    public function testCreateAssistance(array $selectionCriteria)
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
            'commodities' => [$commodity],
            'selectionCriteria' => $selectionCriteria,
            'foodLimit' => 10.99,
            'nonFoodLimit' => null,
            'cashbackLimit' => 1024,
            'remoteDistributionAllowed' => $commodity['modalityType']==\NewApiBundle\Enum\ModalityType::SMART_CARD ? false : null,
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
    }

}
