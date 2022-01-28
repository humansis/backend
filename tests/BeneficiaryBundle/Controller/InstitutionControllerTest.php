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
use NewApiBundle\Enum\NationalIdType;
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
                    'type' => NationalIdType::NATIONAL_ID,
                    'number' => '000-1234-5895-21',
                ],
                'phone_type' => 'Mobile',
                'phone_prefix' => '+4234',
                'phone_number' => '123 456 789',
                'contact_name' => 'Abdul Mohammad',
                'contact_family_name' => 'Qousad',
                '__country' => 'KHM',
                'projects' => [1],
            ]],
            'minimalistic' => [[
                'name' => 'Local mayor institution',
                'type' => Institution::TYPE_GOVERNMENT,
                'projects' => [1],
            ]],
            'minimalistic with street name' => [[
                'name' => 'Local mayor institution',
                'type' => Institution::TYPE_COMMUNITY_CENTER,
                'address' => [
                    'street' => 'Street name',
                ],
                'projects' => [1],
            ]],
            'minimalistic with location' => [[
                'name' => 'Local mayor institution',
                'type' => Institution::TYPE_COMMUNITY_CENTER,
                'address' => [
                    'location' => [
                        'adm1' => 1,
                    ],
                ],
                'projects' => [1],
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
        $this->client = self::$container->get('test.client');
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
        $this->assertArrayHasKey('projects', $institution,"Part of answer missing: projects");

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

            $this->assertSame($institution['address']['street'], $institutionBody['address']['street'] ?? null, "Returned data are different than input: address");
            $this->assertSame($institution['address']['number'], $institutionBody['address']['number'] ?? null, "Returned data are different than input: address");
            $this->assertSame($institution['address']['postcode'], $institutionBody['address']['postcode'] ?? null, "Returned data are different than input: address");
        }

        $this->assertIsArray($institution['projects']);
        $this->assertCount(1, $institution['projects']);

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

}
