<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class DonorControllerTest extends AbstractFunctionalApiTest
{
    public function testCreate()
    {
        $this->client->request('POST', '/api/basic/web-app/v1/donors', [
            'fullname' => 'Test Donor',
            'shortname' => 'TD',
        ], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('fullname', $result);
        $this->assertArrayHasKey('shortname', $result);
        $this->assertArrayHasKey('notes', $result);
        $this->assertArrayHasKey('logo', $result);

        return $result['id'];
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id)
    {
        $this->client->request('PUT', '/api/basic/web-app/v1/donors/'.$id, [
            'fullname' => 'Test Donor',
            'shortname' => 'TD',
            'notes' => 'some note',
        ], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('fullname', $result);
        $this->assertArrayHasKey('shortname', $result);
        $this->assertArrayHasKey('notes', $result);
        $this->assertArrayHasKey('logo', $result);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testGet(int $id)
    {
        $this->client->request('GET', '/api/basic/web-app/v1/donors/'.$id, [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('fullname', $result);
        $this->assertArrayHasKey('shortname', $result);
        $this->assertArrayHasKey('notes', $result);
        $this->assertArrayHasKey('logo', $result);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testList()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/donors?sort[]=fullname.asc&filter[fulltext]=test&filter[id][]=1', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    /**
     * @depends testGet
     */
    public function testDelete(int $id)
    {
        $this->client->request('DELETE', '/api/basic/web-app/v1/donors/'.$id, [], [], $this->addAuth());

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     */
    public function testGetNotexists(int $id)
    {
        $this->client->request('GET', '/api/basic/web-app/v1/donors/'.$id, [], [], $this->addAuth());

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
