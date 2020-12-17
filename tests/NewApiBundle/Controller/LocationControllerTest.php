<?php

namespace Tests\NewApiBundle\Controller;

use Exception;
use Tests\BMSServiceTestCase;

class LocationControllerTest extends BMSServiceTestCase
{
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');
    }

    public function testGetCountries()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/countries');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('iso3', $result['data'][0]);
        $this->assertArrayHasKey('currency', $result['data'][0]);

        return $result['data'][0]['iso3'];
    }

    /**
     * @depends testGetCountries
     */
    public function testGetCountry($iso3)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/countries/'.$iso3);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertArrayHasKey('currency', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetListOfAdm1()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/adm1');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('id', $result['data'][0]);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('code', $result['data'][0]);
        $this->assertArrayHasKey('countryIso3', $result['data'][0]);
        $this->assertArrayHasKey('locationId', $result['data'][0]);

        return $result['data'][0]['id'];
    }

    /**
     * @depends testGetListOfAdm1
     */
    public function testGetDetailOfAdm1($id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/adm1/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('countryIso3', $result);
        $this->assertArrayHasKey('locationId', $result);
    }

    /**
     * @depends testGetListOfAdm1
     */
    public function testGetListOfAdm2($id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/adm1/'.$id.'/adm2');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('id', $result['data'][0]);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('code', $result['data'][0]);
        $this->assertArrayHasKey('locationId', $result['data'][0]);
        $this->assertArrayHasKey('adm1Id', $result['data'][0]);

        return $result['data'][0]['id'];
    }

    /**
     * @depends testGetListOfAdm2
     */
    public function testGetDetailOfAdm2($id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/adm2/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('locationId', $result);
        $this->assertArrayHasKey('adm1Id', $result);
    }

    /**
     * @depends testGetListOfAdm2
     */
    public function testGetListOfAdm3($id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/adm2/'.$id.'/adm3');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('id', $result['data'][0]);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('code', $result['data'][0]);
        $this->assertArrayHasKey('locationId', $result['data'][0]);
        $this->assertArrayHasKey('adm2Id', $result['data'][0]);

        return $result['data'][0]['id'];
    }

    /**
     * @depends testGetListOfAdm3
     */
    public function testGetDetailOfAdm3($id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/adm3/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('locationId', $result);
        $this->assertArrayHasKey('adm2Id', $result);
    }

    /**
     * @depends testGetListOfAdm3
     */
    public function testGetListOfAdm4($id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/adm3/'.$id.'/adm4');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('id', $result['data'][0]);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('code', $result['data'][0]);
        $this->assertArrayHasKey('locationId', $result['data'][0]);
        $this->assertArrayHasKey('adm3Id', $result['data'][0]);

        return $result['data'][0]['id'];
    }

    /**
     * @depends testGetListOfAdm4
     */
    public function testGetDetailOfAdm4($id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/adm4/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('locationId', $result);
        $this->assertArrayHasKey('adm3Id', $result);
    }
}
