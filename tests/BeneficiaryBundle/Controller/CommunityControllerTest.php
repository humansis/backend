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
                'id_type' => 'national',
                'id_number' => '000-1234-5895-21',
                'phone_prefix' => '+4234',
                'phone_number' => '123 456 789',
                'contact_name' => 'Abdul Mohammad',
                'contact_family_name' => 'Qousad',
                '__country' => 'KHM'
            ]],
            'minimalistic' => [[
                '__country' => 'KHM'
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

        $this->assertSame($community['contact_name'], $communityBody['contact_name'] ?? '', "Returned data are different than input: contact_name");
        $this->assertSame($community['contact_family_name'], $communityBody['contact_family_name'] ?? '', "Returned data are different than input: contact_name");
        $this->assertSame($community['phone_prefix'], $communityBody['phone_prefix'] ?? '', "Returned data are different than input: phone_prefix");
        $this->assertSame($community['phone_number'], $communityBody['phone_number'] ?? '', "Returned data are different than input: phone_number");
        $this->assertSame($community['longitude'], $communityBody['longitude'] ?? '', "Returned data are different than input: longitude");;
        $this->assertSame($community['latitude'], $communityBody['latitude'] ?? '', "Returned data are different than input: latitude");;

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
        $crawler = $this->request('POST', '/api/wsse/communities/get/all', $body);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $communitiesArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, count($communitiesArray[1]));
        $communities = $communitiesArray[1];
        if (!empty($communities)) {
            $community = current($communities);

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

        $crawler = $this->request('POST', '/api/wsse/communities/' . $community->getId(), $changes);
        $communitiesArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->assertArrayHasKey('longitude', $communitiesArray,"Part of answer missing: longitude");
        $this->assertArrayHasKey('latitude', $communitiesArray,"Part of answer missing: latitude");
        $this->assertEquals($communitiesArray['longitude'], $changes['longitude'], "Longitude wasn't changed");
        $this->assertEquals($communitiesArray['latitude'], $changes['latitude'], "Latitude wasn't changed");
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

        $crawler = $this->request('POST', '/api/wsse/communities/' . $community->getId(), $changes);
        $communitiesArray = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->assertArrayHasKey('address', $communitiesArray,"Part of answer missing: address");
        $this->assertEquals($communitiesArray['address']['street'], $changes['address']['street'], "Address[street] wasn't changed");
        $this->assertEquals($communitiesArray['address']['number'], $changes['address']['number'], "Address[number] wasn't changed");
        $this->assertEquals($communitiesArray['address']['postcode'], $changes['address']['postcode'], "Address[postcode] wasn't changed");
    }
}
