<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class CountrySpecificControllerTest extends AbstractFunctionalApiTest
{
    public function testCreate()
    {
        $this->client->request('POST', '/api/basic/web-app/v1/country-specifics', [
            'field' => 'Country specific field',
            'type' => 'number',
            'iso3' => 'KHM',
        ], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('PUT', '/api/basic/web-app/v1/country-specifics/'.$id, [
            'field' => 'Country specific field',
            'type' => 'text',

        ], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/country-specifics/'.$id, [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('DELETE', '/api/basic/web-app/v1/country-specifics/'.$id, [], [], $this->addAuth());

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     */
    public function testGetNotexists(int $id)
    {
        $this->client->request('GET', '/api/basic/web-app/v1/country-specifics/'.$id, [], [], $this->addAuth());

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testGetAnswer()
    {
        /** @var CountrySpecificAnswer $answer */
        $answer = self::$container->get('doctrine')->getRepository(CountrySpecificAnswer::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('GET', '/api/basic/web-app/v1/country-specifics/answers/'.$answer->getId(), [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonStringEqualsJsonString('{
            "id": '.$answer->getId().',
            "countrySpecificOptionId": '.$answer->getCountrySpecific()->getId().',
            "answer": "'.$answer->getAnswer().'"
        }', $this->client->getResponse()->getContent());
    }
}
