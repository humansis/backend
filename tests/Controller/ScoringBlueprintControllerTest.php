<?php

declare(strict_types=1);

namespace Tests\Controller;

use Exception;
use Enum\ProductCategoryType;
use DBAL\SectorEnum;
use Tests\BMSServiceTestCase;

class ScoringBlueprintControllerTest extends BMSServiceTestCase
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::getContainer()->get('test.client');
    }

    public function testCreateMissingData()
    {
        $this->request('POST', '/api/basic/web-app/v1/scoring-blueprints', [
            'name' => 'Scoring 1',
        ]);
        $response = $this->client->getResponse();
        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST,
            $response->getStatusCode(),
            'Expected different response code'
        );
    }

    public function testCreateInvalidCsv()
    {
        $this->request('POST', '/api/basic/web-app/v1/scoring-blueprints', [
            'name' => 'Scoring 1',
            'content' => 'NDU0NTY=',
        ]);
        $response = $this->client->getResponse();
        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST,
            $response->getStatusCode(),
            'Expected different response code'
        );
    }

    public function testCreateSuccess()
    {
        $this->request('POST', '/api/basic/web-app/v1/scoring-blueprints', [
            'name' => 'Scoring 1',
            'content' => 'UnVsZSB0eXBlLEZpZWxkIE5hbWUsVGl0bGUsT3B0aW9ucyxQb2ludHM=',
        ]);
        $response = $this->client->getResponse();
        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_CREATED,
            $response->getStatusCode(),
            'Expected different response code'
        );
        $data = json_decode($response->getContent(), null, 512, JSON_THROW_ON_ERROR);

        return $data->id;
    }

    /**
     * @depends testCreateSuccess
     */
    public function testList()
    {
        $this->request('GET', '/api/basic/web-app/v1/scoring-blueprints');
        $response = $this->client->getResponse();
        $result = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $response->getStatusCode(),
            'Expected different response code'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);

        foreach ($result['data'] as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('createdAt', $item);
            $this->assertArrayHasKey('archived', $item);
        }
    }

    /**
     * @depends testCreateSuccess
     */
    public function testUpdate(int $id)
    {
        $this->request(
            'PATCH',
            '/api/basic/web-app/v1/scoring-blueprints/' . $id,
            $data = [
                'name' => 'New name',
            ]
        );

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals($data['name'], $result['name']);

        return $id;
    }

    /**
     * @depends testCreateSuccess
     */
    public function testGet(int $id)
    {
        $this->request('GET', '/api/basic/web-app/v1/scoring-blueprints/' . $id);

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);

        return $id;
    }

    /**
     * @depends testCreateSuccess
     */
    public function testDelete(int $id)
    {
        $this->request('DELETE', '/api/basic/web-app/v1/scoring-blueprints/' . $id);

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    public function testGetNotexists()
    {
        $this->request('GET', '/api/basic/web-app/v1/scoring-blueprints/-1');

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
