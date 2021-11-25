<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Camp;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Enum\ResidencyStatus;
use CommonBundle\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ProjectBundle\Entity\Project;
use ProjectBundle\Enum\Livelihood;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class HouseholdControllerTest extends AbstractFunctionalApiTest
{
    public function testCreate()
    {
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('POST', '/api/basic/web-app/v1/households', [
            'livelihood' => Livelihood::DAILY_LABOUR,
            'iso3' => 'KHM',
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
                            'type' => NationalId::TYPE_NATIONAL_ID,
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
                            'type' => NationalId::TYPE_NATIONAL_ID,
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
                ]
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
                'type' => NationalId::TYPE_NATIONAL_ID,
            ],
            'proxyPhone' => [
                'prefix' => '420',
                'number' => '123456789',
                'type' => 'Landline',
                'proxy' => true,
            ],
        ], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $vulnerabilityCriterion = $em->getRepository(VulnerabilityCriterion::class)->findBy([], ['id' => 'asc'])[0];
        $location = $em->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];
        $camp = $em->getRepository(Camp::class)->findBy([], ['id' => 'asc'])[0];

        /** @var Household $household */
        $household = $em->getRepository(Household::class)->find($id);
        $householdHead = $household->getHouseholdHead();

        $member = null;
        foreach ($household->getBeneficiaries() as $beneficiary) {
            if (!$beneficiary->isHead()) {
                $member = $beneficiary;
            }
        }

        $this->client->request('PUT', '/api/basic/web-app/v1/households/'.$id, [
            'livelihood' => Livelihood::FARMING_AGRICULTURE,
            'iso3' => 'KHM',
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
                            'type' => NationalId::TYPE_NATIONAL_ID,
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
                            'type' => NationalId::TYPE_NATIONAL_ID,
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
                'type' => NationalId::TYPE_NATIONAL_ID,
            ],
            'proxyPhone' => [
                'prefix' => '420',
                'number' => '123456789',
                'type' => 'Landline',
                'proxy' => true,
            ],
        ], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/households/'.$id, [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/households?sort[]=localFirstName.asc&filter[gender]=F', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    /**
     * @depends testGet
     */
    public function testDelete(int $id)
    {
        $this->client->request('DELETE', '/api/basic/web-app/v1/households/'.$id, [], [], $this->addAuth());

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     */
    public function testGetNotexists(int $id)
    {
        $this->client->request('GET', '/api/basic/web-app/v1/households/'.$id, [], [], $this->addAuth());

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
        $household = $em->getRepository(Household::class)->findOneBy([], ['id'=>'desc']);

        $this->client->request('PUT', '/api/basic/web-app/v1/projects/'.$project->getId().'/households', [
            'householdIds' => [$household->getId()],
        ], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
    }
}
