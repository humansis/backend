<?php

namespace Tests\CommonBundle\Controller;

use ProjectBundle\Enum\Livelihood;
use Tests\BMSServiceTestCase;

class LivelihoodControllerTest extends BMSServiceTestCase
{
    /**
     * @throws \Exception
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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testLivelihoods()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/wsse/livelihoods');
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($data);
        $this->assertIsArray($data[0]);
        $this->assertArrayHasKey('value', $data[0]);
        $this->assertArrayHasKey('name', $data[0]);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testFilteredLivelihoods()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/wsse/livelihoods?values[]='.Livelihood::DAILY_LABOUR.'&values[]='.Livelihood::GOVERNMENT);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        $values = array_map(function ($item) {
            return $item['value'];
        }, $data);

        $this->assertTrue(in_array(Livelihood::DAILY_LABOUR, $values));
        $this->assertTrue(in_array(Livelihood::GOVERNMENT, $values));
    }
}
