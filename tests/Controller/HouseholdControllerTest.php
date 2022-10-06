<?php

namespace Tests\Controller;

use Entity\Camp;
use Entity\Household;
use Entity\NationalId;
use Entity\VulnerabilityCriterion;
use Enum\ResidencyStatus;
use Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Enum\NationalIdType;
use Entity\Project;
use Enum\Livelihood;
use Tests\BMSServiceTestCase;

class HouseholdControllerTest extends BMSServiceTestCase
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

    public function testCreate()
    {
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];

        $this->request('POST', '/api/basic/web-app/v1/households', [
            'livelihood' => Livelihood::IRREGULAR_EARNINGS,
            'assets' => ['1'],
            'shelterStatus' => '1',
            'projectIds' => [$project->getId()],
            'notes' => 'some notes',
            'longitude' => null,
            'latitude' => null,
            'beneficiaries' => [
                [
                    'dateOfBirth' => '2000-12-01',
                    'localFamilyName' => 'Bond',
                    'localGivenName' => 'James',
                    'enFamilyName' => null,
                    'enGivenName' => null,
                    'gender' => 'M',
                    'residencyStatus' => ResidencyStatus::REFUGEE,
                    'nationalIdCards' => [
                        [
                            'number' => '022-33-1547',
                            'type' => NationalIdType::NATIONAL_ID,
                        ],
                    ],
                    'phones' => [
                        [
                            'prefix' => '420',
                            'number' => '123456789',
                            'type' => 'Landline',
                            'proxy' => true,
                        ],
                    ],
                    'referralType' => '1',
                    'referralComment' => 'string',
                    'isHead' => true,
                    'vulnerabilityCriteria' => [VulnerabilityCriterion::CRITERION_DISABLED],
                ],
                [
                    'dateOfBirth' => '2000-12-01',
                    'localFamilyName' => 'Simpson',
                    'localGivenName' => 'Homer',
                    'enFamilyName' => null,
                    'enGivenName' => null,
                    'gender' => 'M',
                    'residencyStatus' => ResidencyStatus::REFUGEE,
                    'nationalIdCards' => [
                        [
                            'number' => '022-33-1548',
                            'type' => NationalIdType::NATIONAL_ID,
                        ],
                    ],
                    'phones' => [
                        [
                            'prefix' => '420',
                            'number' => '123456789',
                            'type' => 'Landline',
                            'proxy' => true,
                        ],
                    ],
                    'referralType' => '1',
                    'referralComment' => 'string',
                    'isHead' => false,
                    'vulnerabilityCriteria' => [VulnerabilityCriterion::CRITERION_DISABLED, VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL],
                ],
            ],
            'incomeLevel' => 0,
            'foodConsumptionScore' => 0,
            'copingStrategiesIndex' => 0,
            'debtLevel' => 0,
            'supportDateReceived' => '2020-01-01',
            'supportReceivedTypes' => ['0'],
            'supportOrganizationName' => 'some organisation',
            'incomeSpentOnFood' => 0,
            'houseIncome' => null,
            'countrySpecificAnswers' => [ //for KHM
                [
                    'countrySpecificId' => 1,
                    'answer' => '2',
                ],
            ],
            'residenceAddress' => [
                'number' => 'string',
                'street' => 'string',
                'postcode' => 'string',
                'locationId' => $location->getId(),
            ],
            'campAddress' => [
                'tentNumber' => 'string',
                'camp' => [
                    'name' => 'string',
                    'locationId' => $location->getId(),
                ],
            ],
            'proxyLocalFamilyName' => 'Bond',
            'proxyLocalGivenName' => 'James',
            'proxyLocalParentsName' => 'Jones',
            'proxyEnFamilyName' => null,
            'proxyEnGivenName' => null,
            'proxyEnParentsName' => null,
            'proxyNationalIdCard' => [
                'number' => '022-33-1547',
                'type' => NationalIdType::NATIONAL_ID,
            ],
            'proxyPhone' => [
                'prefix' => '420',
                'number' => '123456789',
                'type' => 'Landline',
                'proxy' => true,
            ],
        ], [], [
            'HTTP_COUNTRY' => $location->getCountryISO3(),
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('livelihood', $result);
        $this->assertArrayHasKey('assets', $result);
        $this->assertArrayHasKey('shelterStatus', $result);
        $this->assertArrayHasKey('projectIds', $result);
        $this->assertArrayHasKey('notes', $result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertArrayHasKey('projectIds', $result);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertArrayHasKey('householdHeadId', $result);
        $this->assertArrayHasKey('beneficiaryIds', $result);
        $this->assertArrayHasKey('incomeLevel', $result);
        $this->assertArrayHasKey('foodConsumptionScore', $result);
        $this->assertArrayHasKey('copingStrategiesIndex', $result);
        $this->assertArrayHasKey('debtLevel', $result);
        $this->assertArrayHasKey('supportDateReceived', $result);
        $this->assertArrayHasKey('supportReceivedTypes', $result);
        $this->assertArrayHasKey('supportOrganizationName', $result);
        $this->assertArrayHasKey('incomeSpentOnFood', $result);
        $this->assertArrayHasKey('householdIncome', $result);
        $this->assertArrayHasKey('campAddressId', $result);
        $this->assertArrayHasKey('residenceAddressId', $result);
        $this->assertArrayHasKey('temporarySettlementAddressId', $result);
        $this->assertArrayHasKey('proxyLocalFamilyName', $result);
        $this->assertArrayHasKey('proxyLocalGivenName', $result);
        $this->assertArrayHasKey('proxyLocalParentsName', $result);
        $this->assertArrayHasKey('proxyEnFamilyName', $result);
        $this->assertArrayHasKey('proxyEnGivenName', $result);
        $this->assertArrayHasKey('proxyEnParentsName', $result);
        $this->assertArrayHasKey('proxyNationalIdCardId', $result);
        $this->assertArrayHasKey('proxyPhoneId', $result);

        return $result['id'];
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id)
    {
        $vulnerabilityCriterion = self::$container->get('doctrine')->getRepository(VulnerabilityCriterion::class)->findBy([], ['id' => 'asc'])[0];
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];
        $camp = self::$container->get('doctrine')->getRepository(Camp::class)->findBy([], ['id' => 'asc'])[0];

        /** @var Household $household */
        $household = $this->em->getRepository(Household::class)->find($id);
        $householdHead = $household->getHouseholdHead();

        $member = null;
        foreach ($household->getBeneficiaries() as $beneficiary) {
            if (!$beneficiary->isHead()) {
                $member = $beneficiary;
            }
        }

        $this->request('PUT', '/api/basic/web-app/v1/households/' . $id, [
            'livelihood' => Livelihood::FARMING_AGRICULTURE,
            'assets' => ['1', '2'],
            'shelterStatus' => '1',
            'projectIds' => [],
            'notes' => 'some notes',
            'longitude' => null,
            'latitude' => null,
            'beneficiaries' => [
                [
                    'id' => $householdHead->getId(),
                    'dateOfBirth' => '2000-12-01',
                    'localFamilyName' => 'Bond',
                    'localGivenName' => 'James',
                    'enFamilyName' => null,
                    'enGivenName' => null,
                    'gender' => 'M',
                    'residencyStatus' => ResidencyStatus::REFUGEE,
                    'nationalIdCards' => [
                        [
                            'number' => '022-33-1547',
                            'type' => NationalIdType::NATIONAL_ID,
                        ],
                    ],
                    'phones' => [
                        [
                            'prefix' => '420',
                            'number' => '123456789',
                            'type' => 'Landline',
                            'proxy' => true,
                        ],
                    ],
                    'referralType' => '1',
                    'referralComment' => 'string',
                    'isHead' => true,
                    'vulnerabilityCriteria' => [VulnerabilityCriterion::CRITERION_DISABLED],
                ],
                [
                    'id' => $member->getId(),
                    'dateOfBirth' => '2000-12-01',
                    'localFamilyName' => 'Simpson',
                    'localGivenName' => 'Homer',
                    'enFamilyName' => null,
                    'enGivenName' => null,
                    'gender' => 'M',
                    'residencyStatus' => ResidencyStatus::REFUGEE,
                    'nationalIdCards' => [
                        [
                            'number' => '022-33-1548',
                            'type' => NationalIdType::NATIONAL_ID,
                        ],
                    ],
                    'phones' => [
                        [
                            'prefix' => '420',
                            'number' => '123456789',
                            'type' => 'Landline',
                            'proxy' => true,
                        ],
                    ],
                    'referralType' => '1',
                    'referralComment' => 'string',
                    'isHead' => false,
                    'vulnerabilityCriteria' => [],
                ],
            ],
            'incomeLevel' => 0,
            'foodConsumptionScore' => 0,
            'copingStrategiesIndex' => 0,
            'debtLevel' => 0,
            'supportDateReceived' => '2020-01-01',
            'supportReceivedTypes' => ['0'],
            'supportOrganizationName' => 'some organisation',
            'incomeSpentOnFood' => 0,
            'houseIncome' => null,
            'countrySpecificAnswers' => [],
            'residenceAddress' => [
                'number' => 'string',
                'street' => 'string',
                'postcode' => 'string',
                'locationId' => $location->getId(),
            ],
            'campAddress' => [
                'tentNumber' => 'string',
                'campId' => $camp->getId(),
            ],
            'proxyLocalFamilyName' => 'Bond',
            'proxyLocalGivenName' => 'James',
            'proxyLocalParentsName' => 'Jones',
            'proxyEnFamilyName' => null,
            'proxyEnGivenName' => null,
            'proxyEnParentsName' => null,
            'proxyNationalIdCard' => [
                'number' => '022-33-1547',
                'type' => NationalIdType::NATIONAL_ID,
            ],
            'proxyPhone' => [
                'prefix' => '420',
                'number' => '123456789',
                'type' => 'Landline',
                'proxy' => true,
            ],
        ], [], ['HTTP_COUNTRY' => $location->getCountryISO3()]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('livelihood', $result);
        $this->assertArrayHasKey('assets', $result);
        $this->assertArrayHasKey('shelterStatus', $result);
        $this->assertArrayHasKey('projectIds', $result);
        $this->assertArrayHasKey('notes', $result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertArrayHasKey('projectIds', $result);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertArrayHasKey('householdHeadId', $result);
        $this->assertArrayHasKey('beneficiaryIds', $result);
        $this->assertArrayHasKey('countrySpecificAnswerIds', $result);
        $this->assertArrayHasKey('incomeLevel', $result);
        $this->assertArrayHasKey('foodConsumptionScore', $result);
        $this->assertArrayHasKey('copingStrategiesIndex', $result);
        $this->assertArrayHasKey('debtLevel', $result);
        $this->assertArrayHasKey('supportDateReceived', $result);
        $this->assertArrayHasKey('supportReceivedTypes', $result);
        $this->assertArrayHasKey('supportOrganizationName', $result);
        $this->assertArrayHasKey('incomeSpentOnFood', $result);
        $this->assertArrayHasKey('householdIncome', $result);
        $this->assertArrayHasKey('campAddressId', $result);
        $this->assertArrayHasKey('residenceAddressId', $result);
        $this->assertArrayHasKey('temporarySettlementAddressId', $result);
        $this->assertArrayHasKey('proxyLocalFamilyName', $result);
        $this->assertArrayHasKey('proxyLocalGivenName', $result);
        $this->assertArrayHasKey('proxyLocalParentsName', $result);
        $this->assertArrayHasKey('proxyEnFamilyName', $result);
        $this->assertArrayHasKey('proxyEnGivenName', $result);
        $this->assertArrayHasKey('proxyEnParentsName', $result);
        $this->assertArrayHasKey('proxyNationalIdCardId', $result);
        $this->assertArrayHasKey('proxyPhoneId', $result);

        $this->assertEquals(2, $household->getBeneficiaries()->count());

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testGet(int $id)
    {
        $this->request('GET', '/api/basic/web-app/v1/households/' . $id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('livelihood', $result);
        $this->assertArrayHasKey('assets', $result);
        $this->assertArrayHasKey('shelterStatus', $result);
        $this->assertArrayHasKey('projectIds', $result);
        $this->assertArrayHasKey('notes', $result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertArrayHasKey('projectIds', $result);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertArrayHasKey('householdHeadId', $result);
        $this->assertArrayHasKey('beneficiaryIds', $result);
        $this->assertArrayHasKey('countrySpecificAnswerIds', $result);
        $this->assertArrayHasKey('incomeLevel', $result);
        $this->assertArrayHasKey('foodConsumptionScore', $result);
        $this->assertArrayHasKey('copingStrategiesIndex', $result);
        $this->assertArrayHasKey('debtLevel', $result);
        $this->assertArrayHasKey('supportDateReceived', $result);
        $this->assertArrayHasKey('supportReceivedTypes', $result);
        $this->assertArrayHasKey('supportOrganizationName', $result);
        $this->assertArrayHasKey('incomeSpentOnFood', $result);
        $this->assertArrayHasKey('householdIncome', $result);
        $this->assertArrayHasKey('campAddressId', $result);
        $this->assertArrayHasKey('residenceAddressId', $result);
        $this->assertArrayHasKey('temporarySettlementAddressId', $result);
        $this->assertArrayHasKey('proxyLocalFamilyName', $result);
        $this->assertArrayHasKey('proxyLocalGivenName', $result);
        $this->assertArrayHasKey('proxyLocalParentsName', $result);
        $this->assertArrayHasKey('proxyEnFamilyName', $result);
        $this->assertArrayHasKey('proxyEnGivenName', $result);
        $this->assertArrayHasKey('proxyEnParentsName', $result);
        $this->assertArrayHasKey('proxyNationalIdCardId', $result);
        $this->assertArrayHasKey('proxyPhoneId', $result);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testList()
    {
        $this->request('GET', '/api/basic/web-app/v1/households?sort[]=localFirstName.asc&filter[gender]=F');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    /**
     * @depends testGet
     */
    public function testDelete(int $id)
    {
        $this->request('DELETE', '/api/basic/web-app/v1/households/' . $id);

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     */
    public function testGetNotexists(int $id)
    {
        $this->request('GET', '/api/basic/web-app/v1/households/' . $id);

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    /**
     * @throws Exception
     */
    public function testAddHouseholdToProject()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $project = $em->getRepository(Project::class)->findOneBy([], ['id' => 'asc']);
        $household = $em->getRepository(Household::class)->findOneBy([], ['id' => 'desc']);

        $this->request('PUT', '/api/basic/web-app/v1/projects/' . $project->getId() . '/households', [
            'householdIds' => [$household->getId()],
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
    }
}
