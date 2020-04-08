<?php


namespace Tests\BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Profile;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use Mpdf\Tag\Ins;
use ProjectBundle\Entity\Project;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;

class InstitutionControllerTest extends BMSServiceTestCase
{
    public function getValidInstitutions()
    {
        return [
            'fullInput' => [[
                'institution' => [
                    'type' => Institution::TYPE_GOVERNMENT,
                    'longitude' => '20,254871',
                    'latitude' => '45,47854425',
                    'address' => [
                        'street' => 'Street name',
                        'number' => '1234',
                        'postcode' => '147 58',
                        'location' => [
                            'adm1' => 1,
                            'adm2' => 1,
                            'adm3' => 1,
                            'adm4' => 1,
                            'country_iso3' => 'KHM',
                        ],
                    ],
                ],
                '__country' => 'KHM'
            ]],
            'minimalistic' => [[
                'institution' => [
                    'type' => Institution::TYPE_GOVERNMENT,
                ],
            ]],
        ];
    }

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("jms_serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');
    }

    /**
     * @dataProvider getValidInstitutions
     * @param $institutionBody
     * @return array
     */
    public function testCreateInstitution($institutionBody)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('PUT', '/api/wsse/institutions', $institutionBody);
        $institution = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: " . $this->client->getResponse()->getContent());

        $this->assertArrayHasKey('type', $institution, "Part of answer missing: type");
        $this->assertArrayHasKey('longitude', $institution,"Part of answer missing: longitude");
        $this->assertArrayHasKey('latitude', $institution,"Part of answer missing: latitude");
        $this->assertArrayHasKey('address', $institution,"Part of answer missing: address");

        $this->assertSame($institution['type'], $institutionBody['institution']['type'], "Returned data are different than input: type");
        if (isset($institutionBody['institution']['longitude'])) {
            $this->assertSame($institution['longitude'], $institutionBody['institution']['longitude'], "Returned data are different than input: longitude");;
        } else {
            $this->assertNull($institution['longitude']);
        }
        if (isset($institutionBody['institution']['latitude'])) {
            $this->assertSame($institution['latitude'], $institutionBody['institution']['latitude'], "Returned data are different than input: latitude");;
        } else {
            $this->assertNull($institution['latitude']);
        }

        if ($institution['address'] !== null) {
            $this->assertArrayHasKey('street', $institution['address'],"Part of answer missing: address[street]");
            $this->assertArrayHasKey('number', $institution['address'],"Part of answer missing: address[number]");
            $this->assertArrayHasKey('postcode', $institution['address'],"Part of answer missing: address[postcode]");

            $this->assertSame($institution['address']['street'], $institutionBody['institution']['address']['street'], "Returned data are different than input: address");
            $this->assertSame($institution['address']['number'], $institutionBody['institution']['address']['number'], "Returned data are different than input: address");
            $this->assertSame($institution['address']['postcode'], $institutionBody['institution']['address']['postcode'], "Returned data are different than input: address");
        }

        return $institution;
    }

    /**
     * @depends testCreateInstitution
     */
    public function testGetAllInstitutions()
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
        $body = [
            "pageIndex" => 0,
            "pageSize" => 10,
            "filter" => [],
            "sort" => []
        ];

        $crawler = $this->request('POST', '/api/wsse/institutions/get/all', $body);
        $listInstitution = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->assertIsArray($listInstitution);
        // list size
        $this->assertIsNumeric($listInstitution[0]);
        // item list
        $this->assertIsArray($listInstitution[1]);
        foreach ($listInstitution[1] as $item) {
            $this->assertArrayHasKey('type', $item, "Part of answer missing: type in institution list");
        }
    }

    /**
     * @depends testCreateInstitution
     * @return bool|void
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetInstitutionByPaginator()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
        
        $body = [
            "pageIndex" => 0,
            "pageSize" => 1,
            "filter" => [],
            "sort" => []
        ];
        $crawler = $this->request('POST', '/api/wsse/institutions/get/all', $body);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $institutionsArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, count($institutionsArray[1]));
        $institutions = $institutionsArray[1];
        if (!empty($institutions)) {
            $institution = current($institutions);

            $this->assertArrayHasKey('type', $institution, "Part of answer missing: type");
            $this->assertArrayHasKey('longitude', $institution,"Part of answer missing: longitude");
            $this->assertArrayHasKey('latitude', $institution,"Part of answer missing: latitude");
            $this->assertArrayHasKey('address', $institution,"Part of answer missing: address");
        } else {
            $this->markTestIncomplete("You currently don't have any institution in your database.");
        }
    }

    /**
     * @depends testCreateInstitution
     * @param $institution
     */
    public function testEditInstitutionPosition()
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var Institution $institution */
        $institution = $this->em->getRepository(Institution::class)->findOneBy([]);

        $oldLongitude = $institution->getLongitude();
        $oldLatitude = $institution->getLatitude();
        $changes = [
            'institution' => [
                'longitude' => '1'.$oldLongitude,
                'latitude' => '1'.$oldLatitude,
            ],
        ];

        $crawler = $this->request('POST', '/api/wsse/institutions/' . $institution->getId(), $changes);
        $institutionsArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->assertArrayHasKey('longitude', $institutionsArray,"Part of answer missing: longitude");
        $this->assertArrayHasKey('latitude', $institutionsArray,"Part of answer missing: latitude");
        $this->assertEquals($institutionsArray['longitude'], $changes['institution']['longitude'], "Longitude wasn't changed");
        $this->assertEquals($institutionsArray['latitude'], $changes['institution']['latitude'], "Latitude wasn't changed");
    }

    /**
     * @depends testCreateInstitution
     * @param $institution
     */
    public function testEditInstitutionType()
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var Institution $institution */
        $institution = $this->em->getRepository(Institution::class)->findOneBy([]);

        $changes = [
            'institution' => [
                'type' => Institution::TYPE_COMMERCE,
            ],
        ];

        $crawler = $this->request('POST', '/api/wsse/institutions/' . $institution->getId(), $changes);
        $institutionsArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        echo $this->client->getResponse()->getContent();

        $this->assertArrayHasKey('type', $institutionsArray,"Part of answer missing: type");
        $this->assertEquals($institutionsArray['type'], $changes['institution']['type'], "Type wasn't changed");
    }

    /**
     * @depends testCreateInstitution
     * @param $institution
     */
    public function testEditInstitutionAddress()
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var Institution $institution */
        $institution = $this->em->getRepository(Institution::class)->findOneBy([]);

        $changes = [
            'institution' => [
                'address' => [
                    'street' => 'changed street',
                    'number' => '123456789',
                    'postcode' => '987654321',
                ],
            ],
        ];

        $crawler = $this->request('POST', '/api/wsse/institutions/' . $institution->getId(), $changes);
        $institutionsArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        echo $this->client->getResponse()->getContent();

        $this->assertArrayHasKey('address', $institutionsArray,"Part of answer missing: address");
        $this->assertEquals($institutionsArray['address']['street'], $changes['institution']['address']['street'], "Address[street] wasn't changed");
        $this->assertEquals($institutionsArray['address']['number'], $changes['institution']['address']['number'], "Address[number] wasn't changed");
        $this->assertEquals($institutionsArray['address']['postcode'], $changes['institution']['address']['postcode'], "Address[postcode] wasn't changed");
    }
}
