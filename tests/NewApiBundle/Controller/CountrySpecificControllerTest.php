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
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    public function testCreate()
    {
        $this->request('POST', '/api/basic/web-app/v1/country-specifics', [
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
        $this->request('PUT', '/api/basic/web-app/v1/country-specifics/'.$id, [
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
        $this->request('GET', '/api/basic/web-app/v1/country-specifics/'.$id);

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
        $this->request('DELETE', '/api/basic/web-app/v1/country-specifics/'.$id);

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     */
    public function testGetNotexists(int $id)
    {
        $this->request('GET', '/api/basic/web-app/v1/country-specifics/'.$id);

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testGetAnswer()
    {
        /** @var CountrySpecificAnswer $answer */
        $answer = self::$container->get('doctrine')->getRepository(CountrySpecificAnswer::class)->findBy([], ['id' => 'asc'])[0];

        $this->request('GET', '/api/basic/web-app/v1/country-specifics/answers/'.$answer->getId());

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
