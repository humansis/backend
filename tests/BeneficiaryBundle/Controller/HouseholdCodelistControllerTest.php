<?php

namespace Tests\BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use Exception;
use ProjectBundle\Enum\Livelihood;
use Tests\BMSServiceTestCase;

class HouseholdCodelistControllerTest extends BMSServiceTestCase
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
        $this->client = $this->container->get('test.client');
    }

    /**
     * @throws Exception
     */
    public function testGetLivelihoods()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/wsse/households/livelihoods');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('pageNumber', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(1, $result['pageNumber']);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(Livelihood::values()), $result['totalCount']);
    }

    /**
     * @throws Exception
     */
    public function testGetAssets()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/wsse/households/assets');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('pageNumber', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(1, $result['pageNumber']);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(Household::ASSETS), $result['totalCount']);
    }
}
