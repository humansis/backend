<?php


namespace Tests\BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Profile;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use Mpdf\Tag\Ins;
use NewApiBundle\Enum\NationalIdType;
use ProjectBundle\Entity\Project;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;

class CommunityControllerTest extends BMSServiceTestCase
{
    public function getValidCommunities()
    {
        return [
            'fullInput' => [[
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
                'projects' => [1],
                '__country' => 'KHM'
            ]],
            'minimalistic' => [[
                '__country' => 'KHM',
                'projects' => [1],
            ]],
            'minimalistic with street name' => [[
                'address' => [
                    'street' => 'Street name',
                ],
                'projects' => [1],
            ]],
            'minimalistic with location' => [[
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
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    /**
     * @dataProvider getValidCommunities
     * @param $communityBody
     * @return array
     */
    public function testCreateCommunity($communityBody)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('PUT', '/api/wsse/communities', $communityBody);
        $community = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: " . $this->client->getResponse()->getContent());

        $this->assertArrayHasKey('longitude', $community,"Part of answer missing: longitude");
        $this->assertArrayHasKey('latitude', $community,"Part of answer missing: latitude");
        $this->assertArrayHasKey('address', $community,"Part of answer missing: address");
        $this->assertArrayHasKey('contact_name', $community,"Part of answer missing: contact_name");
        $this->assertArrayHasKey('phone_prefix', $community,"Part of answer missing: phone_prefix");
        $this->assertArrayHasKey('phone_number', $community,"Part of answer missing: phone_number");
        $this->assertArrayHasKey('projects', $community,"Part of answer missing: projects");

        $this->assertSame($community['contact_name'], $communityBody['contact_name'] ?? '', "Returned data are different than input: contact_name");
        $this->assertSame($community['contact_family_name'], $communityBody['contact_family_name'] ?? '', "Returned data are different than input: contact_name");
        $this->assertSame($community['phone_prefix'], $communityBody['phone_prefix'] ?? null, "Returned data are different than input: phone_prefix");
        $this->assertSame($community['phone_number'], $communityBody['phone_number'] ?? null, "Returned data are different than input: phone_number");
        $this->assertSame($community['longitude'], $communityBody['longitude'] ?? '', "Returned data are different than input: longitude");;
        $this->assertSame($community['latitude'], $communityBody['latitude'] ?? '', "Returned data are different than input: latitude");;

        if (isset($community['national_id'])) {
            $this->assertSame($community['national_id']['type'], $communityBody['national_id']['type'] ?? null, "Returned data are different than input: type");
            $this->assertSame($community['national_id']['number'], $communityBody['national_id']['number'] ?? null, "Returned data are different than input: number");
        }

        if ($community['address'] !== null) {
            $this->assertArrayHasKey('street', $community['address'],"Part of answer missing: address[street]");
            $this->assertArrayHasKey('number', $community['address'],"Part of answer missing: address[number]");
            $this->assertArrayHasKey('postcode', $community['address'],"Part of answer missing: address[postcode]");

            $this->assertSame($community['address']['street'], $communityBody['address']['street'] ?? null, "Returned data are different than input: address");
            $this->assertSame($community['address']['number'], $communityBody['address']['number'] ?? null, "Returned data are different than input: address");
            $this->assertSame($community['address']['postcode'], $communityBody['address']['postcode'] ?? null, "Returned data are different than input: address");
        }

        $this->assertIsArray($community['projects']);
        $this->assertCount(1, $community['projects']);

        return $community;
    }

    /**
     * @depends testCreateCommunity
     */
    public function testGetAllCommunities()
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
        $body = [];

        $crawler = $this->request('POST', '/api/wsse/communities/get/all', $body);
        $listCommunity = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->assertIsArray($listCommunity);
        // list size
        $this->assertIsNumeric($listCommunity[0]);
        // item list
        $this->assertIsArray($listCommunity[1]);
    }
}
