<?php

namespace Tests\CommonBundle\Controller;

use Tests\BMSServiceTestCase;

class CommonControllerTest extends BMSServiceTestCase
{
    /** @var string $namefullname */
    private $name = "TEST_SUMMARY_NAME_PHPUNIT";
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
     * @throws \Exception
     */
    public function testGetSummary()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/summary');
        $summary = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->assertContainsOnly('int', $summary);
        $this->assertCount(5, $summary);
    }

    public function testMobileMasterKeyForOfflineAppExists()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/wsse/offline-app/v1/master-key');
        $summary = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('MASTER_KEY', $summary);
        $this->assertArrayHasKey('APP_VERSION', $summary);
        $this->assertArrayHasKey('APP_ID', $summary);
    }

    public function testMobileMasterKeyForVendorAppExists()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser('vendor@example.org');
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/wsse/vendor-app/v1/master-key');
        $summary = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('MASTER_KEY', $summary);
        $this->assertArrayHasKey('APP_VERSION', $summary);
        $this->assertArrayHasKey('APP_ID', $summary);
    }
}
