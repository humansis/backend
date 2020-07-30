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
                'name' => 'Local mayor office',
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
                'national_id' => [
                    'type' => NationalId::TYPE_NATIONAL_ID,
                    'number' => '000-1234-5895-21',
                ],
                'phone_prefix' => '+4234',
                'phone_number' => '123 456 789',
                'contact_name' => 'Abdul Mohammad',
                'contact_family_name' => 'Qousad',
                '__country' => 'KHM'
            ]],
            'minimalistic' => [[
                'name' => 'Local mayor institution',
                'type' => Institution::TYPE_GOVERNMENT,
            ]],
        ];
    }

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("serializer");
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

        $this->assertArrayHasKey('name', $institution, "Part of answer missing: name");
        $this->assertArrayHasKey('type', $institution, "Part of answer missing: type");
        $this->assertArrayHasKey('longitude', $institution,"Part of answer missing: longitude");
        $this->assertArrayHasKey('latitude', $institution,"Part of answer missing: latitude");
        $this->assertArrayHasKey('address', $institution,"Part of answer missing: address");
        $this->assertArrayHasKey('contact_name', $institution,"Part of answer missing: contact_name");
        $this->assertArrayHasKey('national_id', $institution,"Part of answer missing: national_id");
        $this->assertArrayHasKey('phone_prefix', $institution,"Part of answer missing: phone_prefix");
        $this->assertArrayHasKey('phone_number', $institution,"Part of answer missing: phone_number");

        $this->assertSame($institution['name'], $institutionBody['name'], "Returned data are different than input: type");
        $this->assertSame($institution['type'], $institutionBody['type'], "Returned data are different than input: type");
        $this->assertSame($institution['contact_name'], $institutionBody['contact_name'] ?? null, "Returned data are different than input: contact_name");
        if (isset($institution['national_id'])) {
            $this->assertSame($institution['national_id']['type'], $institutionBody['nationalId']['type'] ?? null, "Returned data are different than input: type");
            $this->assertSame($institution['national_id']['number'], $institutionBody['nationalId']['number'] ?? null, "Returned data are different than input: number");
        }
        $this->assertSame($institution['phone_prefix'], $institutionBody['phone_prefix'] ?? null, "Returned data are different than input: phone_prefix");
        $this->assertSame($institution['phone_number'], $institutionBody['phone_number'] ?? null, "Returned data are different than input: phone_number");
        $this->assertSame($institution['longitude'], $institutionBody['longitude'] ?? null, "Returned data are different than input: longitude");;
        $this->assertSame($institution['latitude'], $institutionBody['latitude'] ?? null, "Returned data are different than input: latitude");;

        if ($institution['address'] !== null) {
            $this->assertArrayHasKey('street', $institution['address'],"Part of answer missing: address[street]");
            $this->assertArrayHasKey('number', $institution['address'],"Part of answer missing: address[number]");
            $this->assertArrayHasKey('postcode', $institution['address'],"Part of answer missing: address[postcode]");

            $this->assertSame($institution['address']['street'], $institutionBody['address']['street'], "Returned data are different than input: address");
            $this->assertSame($institution['address']['number'], $institutionBody['address']['number'], "Returned data are different than input: address");
            $this->assertSame($institution['address']['postcode'], $institutionBody['address']['postcode'], "Returned data are different than input: address");
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

        $institution->setLatitude("10.123");
        $institution->setLongitude("20.123");
        $this->em->persist($institution);
        $this->em->flush();

        $changes = [
            'longitude' => '123.10',
            'latitude' => '321.20',
        ];

        $crawler = $this->request('POST', '/api/wsse/institutions/' . $institution->getId(), $changes);
        $institutionsArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->assertArrayHasKey('longitude', $institutionsArray,"Part of answer missing: longitude");
        $this->assertArrayHasKey('latitude', $institutionsArray,"Part of answer missing: latitude");
        $this->assertEquals($institutionsArray['longitude'], $changes['longitude'], "Longitude wasn't changed");
        $this->assertEquals($institutionsArray['latitude'], $changes['latitude'], "Latitude wasn't changed");
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
            'type' => Institution::TYPE_COMMERCE,
        ];

        $crawler = $this->request('POST', '/api/wsse/institutions/' . $institution->getId(), $changes);
        $institutionsArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->assertArrayHasKey('type', $institutionsArray,"Part of answer missing: type");
        $this->assertEquals($institutionsArray['type'], $changes['type'], "Type wasn't changed");
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
            'address' => [
                'street' => 'changed street',
                'number' => '123456789',
                'postcode' => '987654321',
            ],
        ];

        $crawler = $this->request('POST', '/api/wsse/institutions/' . $institution->getId(), $changes);
        $institutionsArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->assertArrayHasKey('address', $institutionsArray,"Part of answer missing: address");
        $this->assertEquals($institutionsArray['address']['street'], $changes['address']['street'], "Address[street] wasn't changed");
        $this->assertEquals($institutionsArray['address']['number'], $changes['address']['number'], "Address[number] wasn't changed");
        $this->assertEquals($institutionsArray['address']['postcode'], $changes['address']['postcode'], "Address[postcode] wasn't changed");
    }
}
