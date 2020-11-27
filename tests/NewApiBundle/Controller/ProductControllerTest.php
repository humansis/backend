<?php

namespace Tests\NewApiBundle\Controller;

use Exception;
use Tests\BMSServiceTestCase;

class ProductControllerTest extends BMSServiceTestCase
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

    public function testCreate()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('POST', '/api/basic/products', [
            'name' => 'Test product',
            'unit' => 'Kg',
            'image' => 'http://example.org/image.jpg',
            'iso3' => 'KHM',
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('unit', $result);
        $this->assertArrayHasKey('image', $result);
        $this->assertArrayHasKey('iso3', $result);

        return $result['id'];
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('PUT', '/api/basic/products/'.$id, [
            'image' => 'http://example.org/image2.jpg',
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('unit', $result);
        $this->assertArrayHasKey('image', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertNull($result['unit']);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testGet(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/products/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('unit', $result);
        $this->assertArrayHasKey('image', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertNull($result['unit']);

        return $id;
    }

    /**
     * @depends testGet
     */
    public function testDelete(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('DELETE', '/api/basic/products/'.$id);

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     */
    public function testGetNotexists(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/products/'.$id);

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
