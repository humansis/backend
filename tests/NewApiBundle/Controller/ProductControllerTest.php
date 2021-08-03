<?php

namespace Tests\NewApiBundle\Controller;

use Exception;
use NewApiBundle\Entity\ProductCategory;
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
        $this->client = self::$container->get('test.client');
    }

    public function testCreate()
    {
        /** @var ProductCategory|null $productCategory */
        $productCategory = self::$container->get('doctrine')->getRepository(ProductCategory::class)->findOneBy([]);

        if (!$productCategory instanceof ProductCategory) {
            $this->markTestSkipped('There needs to be at least one product category in system to complete this test');
        }

        $this->request('POST', '/api/basic/web-app/v1/products', [
            'name' => 'Test product',
            'unit' => 'Kg',
            'image' => 'http://example.org/image.jpg',
            'iso3' => 'KHM',
            'productCategoryId' => $productCategory->getId(),
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
        $this->assertArrayHasKey('productCategoryId', $result);

        return $result['id'];
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id)
    {
        $this->request('PUT', '/api/basic/web-app/v1/products/'.$id, [
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
        $this->assertArrayHasKey('productCategoryId', $result);
        $this->assertNull($result['unit']);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testGet(int $id)
    {
        $this->request('GET', '/api/basic/web-app/v1/products/'.$id);

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
        $this->assertArrayHasKey('productCategoryId', $result);
        $this->assertNull($result['unit']);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testList()
    {
        $this->request('GET', '/api/basic/web-app/v1/products?sort[]=name.asc&filter[id][]=1');

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
     */
    public function testDelete(int $id)
    {
        $this->request('DELETE', '/api/basic/web-app/v1/products/'.$id);

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     */
    public function testGetNotexists(int $id)
    {
        $this->request('GET', '/api/basic/web-app/v1/products/'.$id);

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
