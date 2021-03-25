<?php

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use Exception;
use Tests\BMSServiceTestCase;

class CountrySpecificControllerTest extends BMSServiceTestCase
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
        $this->client = $this->getContainer()->get('test.client');
    }

    public function testCreate()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('POST', '/api/basic/country-specifics', [
            'field' => 'Country specific field',
            'type' => 'number',
            'iso3' => 'KHM',
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('field', $result);
        $this->assertArrayHasKey('type', $result);
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

        $this->request('PUT', '/api/basic/country-specifics/'.$id, [
            'field' => 'Country specific field',
            'type' => 'text',

        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('field', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('iso3', $result);

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

        $this->request('GET', '/api/basic/country-specifics/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('field', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('iso3', $result);

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

        $this->request('DELETE', '/api/basic/country-specifics/'.$id);

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

        $this->request('GET', '/api/basic/country-specifics/'.$id);

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testGetAnswer()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var CountrySpecificAnswer $answer */
        $answer = $this->getContainer()->get('doctrine')->getRepository(CountrySpecificAnswer::class)->findBy([])[0];

        $this->request('GET', '/api/basic/country-specifics/answers/'.$answer->getId());

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonStringEqualsJsonString('{
            "id": '.$answer->getId().',
            "countrySpecificOptionId": '.$answer->getCountrySpecific()->getId().',
            "answer": "'.$answer->getAnswer().'"
        }', $this->client->getResponse()->getContent());
    }
}
