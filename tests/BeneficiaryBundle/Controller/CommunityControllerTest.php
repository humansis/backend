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
use ProjectBundle\Entity\Project;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;

class CommunityControllerTest extends BMSServiceTestCase
{
    public function getValidCommunitys()
    {
        return [
            'fullInput' => [[
                'community' => [
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
                    'id_type' => 'national',
                    'id_number' => '000-1234-5895-21',
                    'phone_prefix' => '+4234',
                    'phone_number' => '123 456 789',
                    'contact_name' => 'Abdul Mohammad Qousad',
                ],
                '__country' => 'KHM'
            ]],
            'minimalistic' => [[
                'community' => [
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
     * @dataProvider getValidCommunitys
     * @param $communityBody
     * @return array
     */
    public function testCreateCommunity($communityBody)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('PUT', '/api/wsse/communitys', $communityBody);
        $community = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: " . $this->client->getResponse()->getContent());

        $this->assertArrayHasKey('longitude', $community,"Part of answer missing: longitude");
        $this->assertArrayHasKey('latitude', $community,"Part of answer missing: latitude");
        $this->assertArrayHasKey('address', $community,"Part of answer missing: address");
        $this->assertArrayHasKey('contact_name', $community,"Part of answer missing: contact_name");
        $this->assertArrayHasKey('id_type', $community,"Part of answer missing: id_type");
        $this->assertArrayHasKey('id_number', $community,"Part of answer missing: id_number");
        $this->assertArrayHasKey('phone_prefix', $community,"Part of answer missing: phone_prefix");
        $this->assertArrayHasKey('phone_number', $community,"Part of answer missing: phone_number");

        $this->assertSame($community['contact_name'], $communityBody['community']['contact_name'] ?? null, "Returned data are different than input: contact_name");
        $this->assertSame($community['id_type'], $communityBody['community']['id_type'] ?? null, "Returned data are different than input: id_type");
        $this->assertSame($community['id_number'], $communityBody['community']['id_number'] ?? null, "Returned data are different than input: id_number");
        $this->assertSame($community['phone_prefix'], $communityBody['community']['phone_prefix'] ?? null, "Returned data are different than input: phone_prefix");
        $this->assertSame($community['phone_number'], $communityBody['community']['phone_number'] ?? null, "Returned data are different than input: phone_number");
        $this->assertSame($community['longitude'], $communityBody['community']['longitude'] ?? null, "Returned data are different than input: longitude");;
        $this->assertSame($community['latitude'], $communityBody['community']['latitude'] ?? null, "Returned data are different than input: latitude");;

        if ($community['address'] !== null) {
            $this->assertArrayHasKey('street', $community['address'],"Part of answer missing: address[street]");
            $this->assertArrayHasKey('number', $community['address'],"Part of answer missing: address[number]");
            $this->assertArrayHasKey('postcode', $community['address'],"Part of answer missing: address[postcode]");

            $this->assertSame($community['address']['street'], $communityBody['community']['address']['street'], "Returned data are different than input: address");
            $this->assertSame($community['address']['number'], $communityBody['community']['address']['number'], "Returned data are different than input: address");
            $this->assertSame($community['address']['postcode'], $communityBody['community']['address']['postcode'], "Returned data are different than input: address");
        }

        return $community;
    }

    /**
     * @depends testCreateCommunity
     */
    public function testGetAllCommunitys()
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

        $crawler = $this->request('POST', '/api/wsse/communitys/get/all', $body);
        $listCommunity = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->assertIsArray($listCommunity);
        // list size
        $this->assertIsNumeric($listCommunity[0]);
        // item list
        $this->assertIsArray($listCommunity[1]);
    }

    /**
     * @depends testCreateCommunity
     * @return bool|void
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetCommunityByPaginator()
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
        $crawler = $this->request('POST', '/api/wsse/communitys/get/all', $body);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $communitysArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, count($communitysArray[1]));
        $communitys = $communitysArray[1];
        if (!empty($communitys)) {
            $community = current($communitys);

            $this->assertArrayHasKey('longitude', $community,"Part of answer missing: longitude");
            $this->assertArrayHasKey('latitude', $community,"Part of answer missing: latitude");
            $this->assertArrayHasKey('address', $community,"Part of answer missing: address");
        } else {
            $this->markTestIncomplete("You currently don't have any community in your database.");
        }
    }

    /**
     * @depends testCreateCommunity
     * @param $community
     */
    public function testEditCommunityPosition()
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var Community $community */
        $community = $this->em->getRepository(Community::class)->findOneBy([]);

        $oldLongitude = $community->getLongitude();
        $oldLatitude = $community->getLatitude();
        $changes = [
            'community' => [
                'longitude' => '1'.$oldLongitude,
                'latitude' => '1'.$oldLatitude,
            ],
        ];

        $crawler = $this->request('POST', '/api/wsse/communitys/' . $community->getId(), $changes);
        $communitysArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->assertArrayHasKey('longitude', $communitysArray,"Part of answer missing: longitude");
        $this->assertArrayHasKey('latitude', $communitysArray,"Part of answer missing: latitude");
        $this->assertEquals($communitysArray['longitude'], $changes['community']['longitude'], "Longitude wasn't changed");
        $this->assertEquals($communitysArray['latitude'], $changes['community']['latitude'], "Latitude wasn't changed");
    }

    /**
     * @depends testCreateCommunity
     * @param $community
     */
    public function testEditCommunityAddress()
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var Community $community */
        $community = $this->em->getRepository(Community::class)->findOneBy([]);

        $changes = [
            'community' => [
                'address' => [
                    'street' => 'changed street',
                    'number' => '123456789',
                    'postcode' => '987654321',
                ],
            ],
        ];

        $crawler = $this->request('POST', '/api/wsse/communitys/' . $community->getId(), $changes);
        $communitysArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->assertArrayHasKey('address', $communitysArray,"Part of answer missing: address");
        $this->assertEquals($communitysArray['address']['street'], $changes['community']['address']['street'], "Address[street] wasn't changed");
        $this->assertEquals($communitysArray['address']['number'], $changes['community']['address']['number'], "Address[number] wasn't changed");
        $this->assertEquals($communitysArray['address']['postcode'], $changes['community']['address']['postcode'], "Address[postcode] wasn't changed");
    }
}
