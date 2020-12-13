<?php

namespace Tests\NewApiBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Tests\BMSServiceTestCase;

class VendorControllerTest extends BMSServiceTestCase
{
    private const VENDOR_USERNAME = 'testvendor@example.org';

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

    public function testInitializeVendorUser(): string
    {
        $this->request('GET', '/api/wsse/initialize/'.self::VENDOR_USERNAME);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());

        $this->assertArrayHasKey('user_id', $response);
        $this->assertArrayHasKey('salt', $response);

        return $response['salt'];
    }

    /**
     * @depends testInitializeVendorUser
     *
     * @param string $salt
     * @return mixed
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreate(string $salt)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('POST', '/api/basic/vendors', [
            'shop' => 'test shop',
            'name' => 'test name',
            'username' => self::VENDOR_USERNAME,
            'salt' => $salt,
            'password' => 'vendor-password',
            'addressStreet' => 'test street',
            'addressNumber' => '1234566',
            'addressPostcode' => '039 98',
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('shop', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('addressStreet', $result);
        $this->assertArrayHasKey('addressNumber', $result);
        $this->assertArrayHasKey('addressPostcode', $result);

        return $result['id'];
    }

    /**
     * @depends testCreate
     *
     * @param int $id
     * @return mixed
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testUpdate(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('PUT', '/api/basic/vendors/'.$id, [
            'shop' => 'edited',
            'name' => 'test name',
            'username' => self::VENDOR_USERNAME,
            'addressStreet' => 'test street',
            'addressNumber' => '1234',
            'addressPostcode' => '0000',
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('shop', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('addressStreet', $result);
        $this->assertArrayHasKey('addressNumber', $result);
        $this->assertArrayHasKey('addressPostcode', $result);

        $this->assertEquals('edited', $result['shop']);
        $this->assertEquals('0000', $result['addressPostcode']);

        return $result['id'];
    }

    /**
     * @depends testUpdate
     *
     * @param int $id
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGet(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/vendors/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('shop', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('addressStreet', $result);
        $this->assertArrayHasKey('addressNumber', $result);
        $this->assertArrayHasKey('addressPostcode', $result);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testList()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/vendors?sort[]=name.asc');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    /**
     * @depends testGet
     *
     * @param int $id
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testDelete(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('DELETE', '/api/basic/vendors/'.$id);

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     *
     * @param int $id
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGetNotExists(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/vendors/'.$id);

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
